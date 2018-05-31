<?php

namespace LeKoala\Base\Dev\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\ORM\DB;
use LeKoala\Base\Extensions\BaseFileExtension;

class DevBuildExtension extends Extension
{
    public function beforeCallActionHandler()
    {
        $renameColumns = $this->owner->getRequest()->getVar('renameColumns');
        if ($renameColumns) {
            $this->displayMessage("<div class='build'><p><b>Renaming columns</b></p><ul>\n\n");

            $this->renameColumns();

            $this->displayMessage("</ul>\n<p><b>Renaming columns finished!</b></p></div>");
        }

        BaseFileExtension::ensureNullForEmptyRecordRelation();
    }

    protected function renameColumns()
    {
        $classes = $this->getDataObjects();

        foreach ($classes as $class) {
            if (!property_exists($class, 'rename_fields')) {
                continue;
            }

            $fields = $class::$rename_fields;

            $schema = DataObject::getSchema();
            $tableName = $schema->baseDataTable($class);

            $dbSchema = DB::get_schema();
            foreach ($fields as $oldName => $newName) {
                if ($dbSchema->hasField($tableName, $oldName)) {
                    $this->displayMessage("<li>Renaming $oldName to $newName in $tableName</li>");
                    $dbSchema->renameField($tableName, $oldName, $newName);
                } else {
                    $this->displayMessage("<li>$oldName is already renamed to $newName in $tableName</li>");
                }
            }
        }
    }

    public function afterCallActionHandler()
    {
        $envIsAllowed = Director::isDev();
        $skipGeneration = $this->owner->getRequest()->getVar('skipgeneration');

        return;
        if ($skipGeneration || !$envIsAllowed) {
            return;
        }

        $this->displayMessage("<div class='build'><p><b>Generating ide helpers</b></p><ul>\n\n");

        $this->generateRepository();

        $this->displayMessage("</ul>\n<p><b>Generating ide helpers finished!</b></p></div>");
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

const FIRST = 'first';
const LAST = 'last';
const RANDOM = 'random';

public static function getOne(\$class, \$idOrWhere) {
    if(is_int(\$idOrWhere)) {
        return \$class::get_by_id(\$class, \$idOrWhere);
    }
    if(is_string(\$idOrWhere)) {
        switch(\$idOrWhere) {
            case self::FIRST:
                return \$class::get()->first();
            case self::LAST:
                return \$class::get()->last();
            case self::RANDOM:
                return \$class::get()->sort('RAND()')->first();
            default:
                return \$class::get_one(\$class, \$idOrWhere);
        }
    }
    if(is_array(\$idOrWhere)) {
        return \$class::get()->filter(\$idOrWhere)->first();
    }
}

public static function getList(\$class, \$filters) {
    \$list = \$class::get();
    if(\$filters) {
        \$list = \$list->filter(\$filters);
    }
    return \$list;
}

CODE;
        foreach ($classes as $lcClass => $class) {
            $classWithoutNS = ClassHelper::getClassWithoutNamespace($class);

            $method = <<<CODE
/**
 * @params int|string|array \$idOrWhere numeric ID or where clause (as string or array)
 * @return $class
 */
public static function $classWithoutNS(\$idOrWhere) {
    return self::getOne(\\$class::class, \$idOrWhere);
}

/**
 * @params array \$filters
 * @return {$class}[]
 */
public static function {$classWithoutNS}List(\$filters = null) {
    return self::getList(\\$class::class, \$filters);
}

CODE;
            $code .= $method;
        }

        $code .= "\n}";

        $dest = Director::baseFolder() . '/mysite/code/Repository.php';
        file_put_contents($dest, $code);

        $this->displayMessage("<li>Repository class generated</li>");
    }

    /**
     * @param $message
     */
    protected function displayMessage($message)
    {
        echo Director::is_cli() ? "\n" . $message . "\n\n" : "$message";
    }
}
