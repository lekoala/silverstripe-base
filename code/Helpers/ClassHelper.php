<?php
namespace LeKoala\Base\Helpers;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Manifest\ClassLoader;

/**
 *
 */
class ClassHelper
{

    /**
     * Get methods on the current class (without its ancestry)
     *
     * @param string $class
     * @return array
     */
    public static function ownMethods($class)
    {
        $array1 = get_class_methods($class);
        if ($parent_class = get_parent_class($class)) {
            $array2 = get_class_methods($parent_class);
            $array3 = array_diff($array1, $array2);
        } else {
            $array3 = $array1;
        }
        return $array3;
    }

    /**
     * Get a class name without namespace
     *
     * @param string|object $class
     * @return string
     */
    public static function getClassWithoutNamespace($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        if (\strpos($class, '\\') === false) {
            return $class;
        }
        return substr(strrchr($class, '\\'), 1);
    }

    /**
     * Given a partial class name, attempt to determine the best module to assign strings to.
     *
     * @param string $class Either a FQN class name, or a non-qualified class name.
     * @return string Name of module
     */
    public static function findModuleForClass($class)
    {
        if (ClassInfo::exists($class)) {
            $module = ClassLoader::inst()
                ->getManifest()
                ->getOwnerModule($class);
            if ($module) {
                return $module->getName();
            }
        }

        // If we can't find a class, see if it needs to be fully qualified
        if (strpos($class, '\\') !== false) {
            return null;
        }

        // Find FQN that ends with $class
        $classes = preg_grep(
            '/' . preg_quote("\\{$class}", '\/') . '$/i',
            ClassLoader::inst()->getManifest()->getClassNames()
        );

        // Find all modules for candidate classes
        $modules = array_unique(array_map(function ($class) {
            $module = ClassLoader::inst()->getManifest()->getOwnerModule($class);
            return $module ? $module->getName() : null;
        }, $classes));

        if (count($modules) === 1) {
            return reset($modules);
        }

        // Couldn't find it! Exists in none, or multiple modules.
        return null;
    }
}
