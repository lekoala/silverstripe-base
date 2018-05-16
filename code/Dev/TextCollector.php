<?php
namespace LeKoala\Base\Dev;

use SilverStripe\i18n\Messages\Reader;
use SilverStripe\i18n\Messages\Writer;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\i18n\TextCollection\i18nTextCollector;
use SilverStripe\Dev\Debug;

class TextCollector extends i18nTextCollector
{
    /**
     * @var boolean
     */
    protected $debug = false;
    /**
     * @var boolean
     */
    protected $clearUnused = false;
    /**
     * @var array
     */
    protected $restrictToModules = [];
    /**
     * @var boolean
     */
    protected $mergeWithExisting = true;
    /**
     * @var boolean
     */
    protected $preventWrite = false;

    /**
     * @param $locale
     */
    public function __construct($locale = null)
    {
        parent::__construct($locale);

        // Somehow the injector is confused so we inject ourself
        $this->reader = Injector::inst()->create(Reader::class);
        $this->writer = Injector::inst()->create(Writer::class);
    }

    /**
     * This is the main method to build the master string tables with the
     * original strings. It will search for existent modules that use the
     * i18n feature, parse the _t() calls and write the resultant files
     * in the lang folder of each module.
     *
     * @uses DataObject->collectI18nStatics()
     *
     * @param array $restrictToModules
     * @param bool $mergeWithExisting Merge new master strings with existing
     * ones already defined in language files, rather than replacing them.
     * This can be useful for long-term maintenance of translations across
     * releases, because it allows "translation backports" to older releases
     * without removing strings these older releases still rely on.
     */
    public function run($restrictToModules = null, $mergeWithExisting = false)
    {
        $entitiesByModule = $this->collect($restrictToModules, $mergeWithExisting);
        if (empty($entitiesByModule)) {
            return;
        }
        if ($this->debug) {
            Debug::message("Debug mode is enabled and no files have been written");
            Debug::dump($entitiesByModule);
            return;
        }

        // Write each module language file
        foreach ($entitiesByModule as $moduleName => $entities) {
            // Skip empty translations
            if (empty($entities)) {
                continue;
            }

            // Clean sorting prior to writing
            ksort($entities);
            $module = ModuleLoader::inst()->getManifest()->getModule($moduleName);
            $this->write($module, $entities);
        }
    }

    /**
     * Extract all strings from modules and return these grouped by module name
     *
     * @param array $restrictToModules
     * @param bool $mergeWithExisting
     * @return array
     */
    public function collect($restrictToModules = null, $mergeWithExisting = null)
    {
        if ($restrictToModules === null) {
            $restrictToModules = $this->getRestrictToModules();
        } else {
            $this->setRestrictToModules($restrictToModules);
        }
        if ($mergeWithExisting === null) {
            $mergeWithExisting = $this->getMergeWithExisting();
        } else {
            $this->setMergeWithExisting($mergeWithExisting);
        }

        $entitiesByModule = $this->getEntitiesByModule();

        // Resolve conflicts between duplicate keys across modules
        $entitiesByModule = $this->resolveDuplicateConflicts($entitiesByModule);

        // Optionally merge with existing master strings
        if ($mergeWithExisting) {
            $entitiesByModule = $this->mergeWithExisting($entitiesByModule);
        }

        // Restrict modules we update to just the specified ones (if any passed)
        if (!empty($restrictToModules)) {
            // Normalise module names
            $modules = array_filter(array_map(function ($name) {
                $module = ModuleLoader::inst()->getManifest()->getModule($name);
                return $module ? $module->getName() : null;
            }, $restrictToModules));
            // Remove modules
            foreach (array_diff(array_keys($entitiesByModule), $modules) as $module) {
                unset($entitiesByModule[$module]);
            }
        }
        return $entitiesByModule;
    }

    /**
     * Merge all entities with existing strings
     *
     * @param array $entitiesByModule
     * @return array
     */
    protected function mergeWithExisting($entitiesByModule)
    {
        // For each module do a simple merge of the default yml with these strings
        foreach ($entitiesByModule as $module => $messages) {
            // Load existing localisations
            $masterFile = "{$this->basePath}/{$module}/lang/{$this->defaultLocale}.yml";
            $existingMessages = $this->getReader()->read($this->defaultLocale, $masterFile);

            // Merge
            if ($existingMessages) {
                $entitiesByModule[$module] = array_merge(
                    $existingMessages,
                    $messages
                );

                // Clear unused
                if ($this->getClearUnused()) {
                    $unusedEntities = array_diff(
                        array_keys($existingMessages),
                        array_keys($messages)
                    );
                    foreach ($unusedEntities as $unusedEntity) {
                        if (strpos($unusedEntity, 'Global.') !== false) {
                            continue;
                        }
                        if ($this->debug) {
                            Debug::message("Removed $unusedEntity");
                        }
                        unset($entitiesByModule[$unusedEntity]);
                    }
                }
            }
        }
        return $entitiesByModule;
    }

    /**
     * Collect all entities grouped by module
     *
     * @return array
     */
    protected function getEntitiesByModule()
    {
        $restrictToModules = $this->getRestrictToModules();

        // A master string tables array (one mst per module)
        $entitiesByModule = array();
        $modules = ModuleLoader::inst()->getManifest()->getModules();
        foreach ($modules as $module) {
            $moduleName = $module->getName();
            if (!in_array($moduleName, $restrictToModules)) {
                continue;
            }

            // we store the master string tables
            $processedEntities = $this->processModule($module);
            if (isset($entitiesByModule[$moduleName])) {
                $entitiesByModule[$moduleName] = array_merge_recursive(
                    $entitiesByModule[$moduleName],
                    $processedEntities
                );
            } else {
                $entitiesByModule[$moduleName] = $processedEntities;
            }

            // Extract all entities for "foreign" modules ('module' key in array form)
            // @see CMSMenu::provideI18nEntities for an example usage
            foreach ($entitiesByModule[$moduleName] as $fullName => $spec) {
                $specModuleName = $moduleName;

                // Rewrite spec if module is specified
                if (is_array($spec) && isset($spec['module'])) {
                    // Normalise name (in case non-composer name is specified)
                    $specModule = ModuleLoader::inst()->getManifest()->getModule($spec['module']);
                    if ($specModule) {
                        $specModuleName = $specModule->getName();
                    }
                    unset($spec['module']);

                    // If only element is defalt, simplify
                    if (count($spec) === 1 && !empty($spec['default'])) {
                        $spec = $spec['default'];
                    }
                }

                // Remove from source module
                if ($specModuleName !== $moduleName) {
                    unset($entitiesByModule[$moduleName][$fullName]);
                }

                // Write to target module
                if (!isset($entitiesByModule[$specModuleName])) {
                    $entitiesByModule[$specModuleName] = [];
                }
                $entitiesByModule[$specModuleName][$fullName] = $spec;
            }
        }
        return $entitiesByModule;
    }

    /**
     * Get the value of clearUnused
     *
     * @return  boolean
     */
    public function getClearUnused()
    {
        return $this->clearUnused;
    }

    /**
     * Set the value of clearUnused
     *
     * @param  boolean  $clearUnused
     *
     * @return  self
     */
    public function setClearUnused($clearUnused)
    {
        $this->clearUnused = $clearUnused;

        return $this;
    }

    /**
     * Get the value of restrictToModules
     *
     * @return  array
     */
    public function getRestrictToModules()
    {
        return $this->restrictToModules;
    }

    /**
     * Set the value of restrictToModules
     *
     * @param  array  $restrictToModules
     *
     * @return  self
     */
    public function setRestrictToModules($restrictToModules)
    {
        $this->restrictToModules = $restrictToModules;

        return $this;
    }

    /**
     * Get the value of mergeWithExisting
     *
     * @return  boolean
     */
    public function getMergeWithExisting()
    {
        return $this->mergeWithExisting;
    }

    /**
     * Set the value of mergeWithExisting
     *
     * @param  boolean  $mergeWithExisting
     *
     * @return  self
     */
    public function setMergeWithExisting($mergeWithExisting)
    {
        $this->mergeWithExisting = $mergeWithExisting;

        return $this;
    }

    /**
     * Get the value of debug
     *
     * @return  boolean
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set the value of debug
     *
     * @param  boolean  $debug
     *
     * @return  self
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }
}
