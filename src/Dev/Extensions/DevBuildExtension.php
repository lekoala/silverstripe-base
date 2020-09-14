<?php

namespace LeKoala\Base\Dev\Extensions;

use Exception;
use SilverStripe\ORM\DB;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use LeKoala\Base\Helpers\FileHelper;
use SilverStripe\Core\Config\Config;
use LeKoala\Base\Helpers\ClassHelper;
use LeKoala\Base\Subsite\SubsiteHelper;
use LeKoala\Base\Extensions\BaseFileExtension;
use LeKoala\ExcelImportExport\ExcelBulkLoader;
use LeKoala\Base\i18n\BaseI18n;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\i18n\i18n;

/**
 * Allow the following functions before dev build
 * - renameColumns
 * - truncateSiteTree
 *
 * Allow the following functions after dev build:
 * - generateRepository
 * - generateQueryTraits
 * - clearCache
 * - clearEmptyFolders
 * - provisionLocales
 *
 * Preserve current subsite
 *
 * @property \SilverStripe\Dev\DevBuildController|\LeKoala\Base\Dev\Extensions\DevBuildExtension $owner
 */
class DevBuildExtension extends Extension
{
    protected $currentSubsite;

    public function beforeCallActionHandler()
    {
        $this->currentSubsite = SubsiteHelper::currentSubsiteID();

        $renameColumns = $this->owner->getRequest()->getVar('fixTableCase');
        if ($renameColumns) {
            $this->displayMessage("<div class='build'><p><b>Fixing tables case</b></p><ul>\n\n");
            $this->fixTableCase();
            $this->displayMessage("</ul>\n<p><b>Tables fixed!</b></p></div>");
        }

        $renameColumns = $this->owner->getRequest()->getVar('renameColumns');
        if ($renameColumns) {
            $this->displayMessage("<div class='build'><p><b>Renaming columns</b></p><ul>\n\n");
            $this->renameColumns();
            $this->displayMessage("</ul>\n<p><b>Columns renamed!</b></p></div>");
        }

        $truncateSiteTree = $this->owner->getRequest()->getVar('truncateSiteTree');
        if ($truncateSiteTree) {
            $this->displayMessage("<div class='build'><p><b>Truncating SiteTree</b></p><ul>\n\n");
            $this->truncateSiteTree();
            $this->displayMessage("</ul>\n<p><b>SiteTree truncated!</b></p></div>");
        }
    }

    protected function fixTableCase()
    {
        if (!Director::isDev()) {
            throw new Exception("Only available in dev mode");
        }

        $conn = DB::get_conn();
        $dbName = $conn->getSelectedDatabase();

        $tablesSql = "SELECT table_name FROM information_schema.tables WHERE table_schema = '$dbName';";

        $result = DB::query($tablesSql);

        //TODO: check list of tables name and match any lowercased one to the right one from the db schema
    }

    protected function truncateSiteTree()
    {
        if (!Director::isDev()) {
            throw new Exception("Only available in dev mode");
        }

        $sql = <<<SQL
        TRUNCATE TABLE ErrorPage;
        TRUNCATE TABLE ErrorPage_Live;
        TRUNCATE TABLE ErrorPage_Versions;
        TRUNCATE TABLE SiteTree;
        TRUNCATE TABLE SiteTree_CrossSubsiteLinkTracking;
        TRUNCATE TABLE SiteTree_EditorGroups;
        TRUNCATE TABLE SiteTree_ImageTracking;
        TRUNCATE TABLE SiteTree_LinkTracking;
        TRUNCATE TABLE SiteTree_Live;
        TRUNCATE TABLE SiteTree_Versions;
        TRUNCATE TABLE SiteTree_ViewerGroups;
SQL;
        DB::query($sql);
        $this->displayMessage($sql);
    }

    /**
     * Loop on all DataObjects and look for rename_columns property
     *
     * It will rename old columns from old_value => new_value
     *
     * @return void
     */
    protected function renameColumns()
    {
        $classes = $this->getDataObjects();

        foreach ($classes as $class) {
            if (!property_exists($class, 'rename_columns')) {
                continue;
            }

            $fields = $class::$rename_columns;

            $schema = DataObject::getSchema();
            $tableName = $schema->baseDataTable($class);

            $dbSchema = DB::get_schema();
            foreach ($fields as $oldName => $newName) {
                if ($dbSchema->hasField($tableName, $oldName)) {
                    if ($dbSchema->hasField($tableName, $newName)) {
                        $this->displayMessage("<li>$oldName still exists in $tableName. Data will be migrated to $newName and old column $oldName will be dropped.</li>");
                        // Migrate data
                        DB::query("UPDATE $tableName SET $newName = $oldName WHERE $newName IS NULL");
                        // Remove column
                        DB::query("ALTER TABLE $tableName DROP COLUMN $oldName");
                    } else {
                        $this->displayMessage("<li>Renaming $oldName to $newName in $tableName</li>");
                        $dbSchema->renameField($tableName, $oldName, $newName);
                    }
                } else {
                    $this->displayMessage("<li>$oldName does not exist anymore in $tableName</li>");
                }

                // Look for fluent
                $fluentTable = $tableName . '_Localised';
                if ($dbSchema->hasTable($fluentTable)) {
                    if ($dbSchema->hasField($fluentTable, $oldName)) {
                        if ($dbSchema->hasField($fluentTable, $newName)) {
                            $this->displayMessage("<li>$oldName still exists in $fluentTable. Data will be migrated to $newName and old column $oldName will be dropped.</li>");
                            // Migrate data
                            DB::query("UPDATE $fluentTable SET $newName = $oldName WHERE $newName IS NULL");
                            // Remove column
                            DB::query("ALTER TABLE $fluentTable DROP COLUMN $oldName");
                        } else {
                            $this->displayMessage("<li>Renaming $oldName to $newName in $fluentTable</li>");
                            $dbSchema->renameField($fluentTable, $oldName, $newName);
                        }
                    } else {
                        $this->displayMessage("<li>$oldName does not exist anymore in $fluentTable</li>");
                    }
                }
            }
        }
    }

    public function afterCallActionHandler()
    {
        // Other helpers
        $clearCache = $this->owner->getRequest()->getVar('clearCache');
        $clearEmptyFolders = $this->owner->getRequest()->getVar('clearEmptyFolders');

        $this->displayMessage("<div class='build'>");
        if ($clearCache) {
            $this->clearCache();
        }
        if ($clearEmptyFolders) {
            $this->clearEmptyFolders();
        }
        $this->displayMessage("</div>");

        // Dev helpers - only accessible in dev mode
        $envIsAllowed = Director::isDev();
        $generateRepository = $this->owner->getRequest()->getVar('generateRepository');
        $generateQueryTraits = $this->owner->getRequest()->getVar('generateQueryTraits');

        if ($generateQueryTraits || $generateRepository) {
            $this->displayMessage("<div class='build'><p><b>Generating ide helpers</b></p><ul>\n\n");
            if (!$envIsAllowed) {
                $this->displayMessage("<strong>Env is not allowed</strong>\n");
            }
        }
        if ($generateQueryTraits) {
            $this->generateQueryTraits();
        }
        if ($generateRepository) {
            $this->generateRepository();
        }
        if ($generateQueryTraits || $generateRepository) {
            $this->displayMessage("</ul>\n<p><b>Generating ide helpers finished!</b></p></div>");
        }

        // Restore subsite
        if ($this->currentSubsite) {
            SubsiteHelper::changeSubsite($this->currentSubsite);
        }

        $provisionLocales = $this->owner->getRequest()->getVar('provisionLocales');
        if ($provisionLocales) {
            $this->displayMessage("<div class='build'><p><b>Provisioning locales</b></p><ul>\n\n");
            $this->provisionLocales();
            $this->displayMessage("</ul>\n<p><b>Locales provisioned!</b></p></div>");
        }
    }

    protected function provisionLocales()
    {
        $locales = BaseI18n::config()->default_locales;
        if (empty($locales)) {
            $this->displayMessage("No locales defined in BaseI18n:default_locales");
            return;
        }

        foreach ($locales as $loc) {
            $Locale = Locale::get()->filter('Locale', $loc)->first();
            $allLocales = i18n::getData()->getLocales();
            if (!$Locale) {
                $Locale = new Locale();
                $Locale->Title = $allLocales[$loc];
                $Locale->Locale = $loc;
                $Locale->URLSegment = BaseI18n::get_lang($loc);
                $Locale->IsGlobalDefault = $loc == i18n::get_locale();
                $Locale->write();
                $this->displayMessage("Locale $loc created<br/>");
            } else {
                $this->displayMessage("Locale $loc already exist<br/>");
            }
        }
    }

    protected function clearCache()
    {
        $this->displayMessage("<strong>Clearing cache folder</strong>");
        $folder = Director::baseFolder() . '/silverstripe-cache';
        if (!is_dir($folder)) {
            $this->displayMessage("silverstripe-cache does not exist in base folder\n");
            return;
        }
        FileHelper::rmDir($folder);
        mkdir($folder, 0755);
        $this->displayMessage("Cleared silverstripe-cache folder\n");
    }

    protected function clearEmptyFolders()
    {
        $this->displayMessage("<strong>Clearing empty folders in assets</strong>");
        $folder = Director::publicFolder() . '/assets';
        if (!is_dir($folder)) {
            $this->displayMessage("assets folder does not exist in public folder\n");
            return;
        }

        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $name => $object) {
            if ($object->isDir()) {
                $path = $object->getPath();
                if (!is_readable($path)) {
                    $this->displayMessage("$path is not readable\n");
                    continue;
                }
                if (!FileHelper::dirContainsChildren($path)) {
                    rmdir($path);
                    $this->displayMessage("Removed $path\n");
                }
            }
        }
    }

    protected function generateQueryTraits()
    {
        $classes = $this->getDataObjects();
        $pages = ClassInfo::subclassesFor('Page');

        $classesWithoutPages = array_diff_key($classes, $pages);

        foreach ($classesWithoutPages as $lcClass => $class) {
            $module = ClassHelper::findModuleForClass($class);

            $moduleName = $module->getName();
            if ($moduleName != 'mysite' && $moduleName != 'app') {
                continue;
            }

            $className = ClassHelper::getClassWithoutNamespace($class);
            $namespace = ClassHelper::getClassNamespace($class);
            $traitName = $className . 'Queries';
            $traitNameNamespace = $namespace . '\\' . $traitName;
            $file = ClassHelper::findFileForClass($class);
            $content = file_get_contents($file);

            // Do we need to insert the trait usage?
            if (strpos($content, "use $traitName;") === false) {
                // properly insert after class opens
                // TODO: does not work if it implements anything
                $newContent = $content;
                $newContent = str_replace("extends DataObject {", "extends DataObject {\n    use $traitName;\n", $newContent);
                if ($namespace) {
                    $newContent = str_replace('use SilverStripe\ORM\DataObject;', "use SilverStripe\ORM\DataObject;\nuse \\$traitNameNamespace;", $newContent);
                }
                if ($newContent != $content) {
                    file_put_contents($file, $newContent);
                    $this->displayMessage("<li>Trait usage added to $className</li>");
                } else {
                    $this->displayMessage("<li style=\"color:red\">Could not add trait to $className</li>");
                }
            }

            // Do we need to generate a trait
            $traitFile = dirname($file) . '/' . $traitName . '.php';
            if (is_file($traitFile)) {
                $traitContent = file_get_contents($traitFile);
                if (strpos($traitContent, '* @autorefresh') === false) {
                    $this->displayMessage("<li>Trait $traitName already exists and is not set to autorefresh</li>");
                    continue;
                }
            }

            // Generate trait
            $code = "<?php\n\n";

            if ($namespace) {
                $code .= "use $class;\n\n";
            }

            // Add default getters
            $code .= <<<CODE
/**
 * @autorefresh - remove this line if you edit this file manually
 */
trait $traitName
{
    /**
     * @params int|string|array \$idOrWhere numeric ID or where clause (as string or array)
     * @return $class
     */
    public static function findOne(\$idOrWhere)
    {
        return \LeKoala\Base\ORM\QueryHelper::findOne(\\$class::class, \$idOrWhere);
    }

    /**
     * @params array \$filters
     * @return \SilverStripe\ORM\DataList|{$class}
     */
    public static function find(\$filters = null)
    {
        return \LeKoala\Base\ORM\QueryHelper::find(\\$class::class, \$filters);
    }

CODE;

            // Add relations getters
            $has_ones = Config::inst()->get($class, 'has_one');
            if (!$has_ones) {
                $has_ones = [];
            }
            foreach ($has_ones as $has_one => $has_one_class) {
                $column = $has_one . 'ID';
                $code .= <<<CODE

    /**
     * @params array \$filters
     * @return \SilverStripe\ORM\DataList|{$class}
     */
    public static function findBy{$column}(\$id)
    {
        return \LeKoala\Base\ORM\QueryHelper::find(\\$class::class, ['{$column}' => \$id]);
    }

CODE;
            }

            // Add indexes getters
            $indexes = Config::inst()->get($class, 'indexes');
            if (!$indexes) {
                $indexes = [];
            }
            foreach ($indexes as $index => $indexes_spec) {
                $column = null;
                $params = "\$id";
                $filters = "['{$column}' => \$id]";
                if ($indexes_spec == true) {
                    $column = $index;
                } elseif (is_array($index_spec)) {
                    if (isset($index_spec['columns'])) {
                        // $column = implode('And', $index['columns']);
                        //TODO: better syntax for this
                        // $params = implode(", ", $index['columns']);
                        // $filters = "['{$column}' => \$id]";
                    }
                }

                if (!$column) {
                    continue;
                }



                $code .= <<<CODE

    /**
     * @params array \$filters
     * @return \SilverStripe\ORM\DataList|{$class}
     */
    public static function findBy{$column}($params)
    {
        return \LeKoala\Base\ORM\QueryHelper::find(\\$class::class, $filters);
    }

CODE;
            }

            $code .= "}\n";

            file_put_contents($traitFile, $code);
            $this->displayMessage("<li>Trait $traitName generated</li>");
        }
    }

    /**
     * @return array
     */
    protected function getDataObjects()
    {
        $classes = ClassInfo::subclassesFor(DataObject::class);
        array_shift($classes); // remove dataobject
        return $classes;
    }

    /**
     * Generate the repository class
     *
     * @return void
     */
    protected function generateRepository()
    {
        $classes = $this->getDataObjects();

        $code = <<<CODE
<?php
// phpcs:ignoreFile -- this is a generated file
class Repository {

CODE;
        foreach ($classes as $lcClass => $class) {
            $classWithoutNS = ClassHelper::getClassWithoutNamespace($class);

            $method = <<<CODE

    /**
     * @params int|string|array \$idOrWhere numeric ID or where clause (as string or array)
     * @return $class
     */
    public static function $classWithoutNS(\$idOrWhere) {
        return \LeKoala\Base\ORM\QueryHelper::findOne(\\$class::class, \$idOrWhere);
    }

    /**
     * @params array \$filters
     * @return \SilverStripe\ORM\DataList|{$class}
     */
    public static function {$classWithoutNS}List(\$filters = null) {
        return \LeKoala\Base\ORM\QueryHelper::find(\\$class::class, \$filters);
    }

CODE;
            $code .= $method;
        }

        $code .= "\n}\n";

        $dest = Director::baseFolder() . '/app/src/Repository.php';
        file_put_contents($dest, $code);

        $this->displayMessage("<li>Repository class generated</li>");
    }

    /**
     * @param $message
     */
    protected function displayMessage($message)
    {
        echo Director::is_cli() ? strip_tags($message) : nl2br($message);
    }
}
