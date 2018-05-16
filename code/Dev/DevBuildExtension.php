<?php

namespace LeKoala\Base\Dev;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use LeKoala\Base\Helpers\ClassHelper;

class DevBuildExtension extends Extension
{

    /**
     */
    public function afterCallActionHandler()
    {
        $envIsAllowed = Director::isDev();
        $skipGeneration = $this->owner->getRequest()->getVar('skipgeneration');

        if ($skipGeneration === null && $envIsAllowed) {
            $this->displayMessage("<div class='build'><p><b>Generating ide helpers</b></p><ul>\n\n");

            $classes = ClassInfo::subclassesFor(DataObject::class);
            array_shift($classes); // remove dataobject

            $code = <<<CODE
<?php
// phpcs:ignoreFile -- this is not a core file
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

            $this->displayMessage("</ul>\n<p><b>Generating ide helpers finished!</b></p></div>");
        }
    }

    /**
     * @param $message
     */
    public function displayMessage($message)
    {
        echo Director::is_cli() ? "\n" . $message . "\n\n" : "<p><b>$message</b></p>";
    }
}
