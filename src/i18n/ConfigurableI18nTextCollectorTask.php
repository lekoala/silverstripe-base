<?php

namespace LeKoala\Base\i18n;

use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayLib;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\Director;
use LeKoala\Base\i18n\TextCollector;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\Tasks\i18nTextCollectorTask;
use SilverStripe\i18n\TextCollection\i18nTextCollector;
use SilverStripe\Control\Controller;

/**
 * A better task for collecting text
 *
 * Use our TextCollector under the hood that actually works...
 */
class ConfigurableI18nTextCollectorTask extends BuildTask
{
    protected static $ignored_once = false;

    private static $segment = 'i18nTextCollectorTask';

    protected $title = "i18n Textcollector Task (configurable)";

    protected $description = "
		Traverses through files in order to collect the 'entity master tables'
		stored in each module.
	";

    /**
     * This is the main method to build the master string tables with the original strings.
     * It will search for existent modules that use the i18n feature, parse the _t() calls
     * and write the resultant files in the lang folder of each module.
     *
     * @uses DataObject->collectI18nStatics()
     */
    public function init()
    {
        $request = $this->getRequest();

        $this->increaseTimeLimitTo();

        $modules = ArrayLib::valuekey(array_keys(ModuleLoader::inst()->getManifest()->getModules()));

        $this->addOption("locale", "Locale to use", BaseI18n::get_lang());
        $this->addOption("merge", "Merge with previous translations", true);
        $this->addOption("auto_translate", "Translate new strings using google api (1s per translation)", false);
        $this->addOption("clear_unused", "Remove keys that are not used anymore", false);
        $this->addOption("debug", "Show debug messages and prevent write", false);
        $this->addOption("module", "Module", null, $modules);

        $options = $this->askOptions();

        $locale = $options['locale'];
        $merge = $options['merge'];
        $module = $options['module'];
        $clearUnused = $options['clear_unused'];
        $debug = $options['debug'];
        $auto_translate = $options['auto_translate'];

        if ($locale && $module) {
            $this->message("Proceeding with locale $locale for module $module");
            $collector = TextCollector::create($locale);
            $collector->setMergeWithExisting($merge);
            $collector->setClearUnused($clearUnused);
            $collector->setDebug($debug);
            $collector->setAutoTranslate($auto_translate);
            $result = $collector->run([$module], $merge);
            if ($result) {
                foreach ($result as $module => $entities) {
                    $this->message("Collected " . count($entities) . " messages for module $module");
                }
            }
        }
    }

    public function isEnabled()
    {
        if (self::$ignored_once) {
            return true;
        }
        // Basically hide the previous task
        self::$ignored_once = true;
        return Director::isDev();
    }
}
