<?php
namespace LeKoala\Base\Helpers;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Core\Extensible;
use SilverStripe\Security\Member;
use SilverStripe\Core\Manifest\Module;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Core\Injector\InjectorLoader;

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
     * Get all classes using the given extension
     *
     * @param string $extension
     * @param boolean $onlyDataObjects
     * @param boolean $strict
     * @return array
     */
    public static function extendedBy($extension, $onlyDataObjects = true, $strict = false)
    {
        $classes = array();
        if ($onlyDataObjects) {
            $classes = self::getValidDataObjects();
        } else {
            $manifest = ClassLoader::inst()->getManifest();
            $classes = $manifest->getClassNames();
        }
        $classes = array_values($classes);

        $extendedClasses = [];
        foreach ($classes as $class) {
            if (Extensible::has_extension($class, $extension, $strict)) {
                $extendedClasses[] = $class;
            }
        }
        return $extendedClasses;
    }

    /**
     * Find class with many_many relations to this class
     *
     * @param string $manyClass
     * @return array
     */
    public static function relatedManyClasses($manyClass)
    {
        $classes = ClassInfo::subclassesFor(DataObject::class);
        array_shift($classes);

        $related = [];
        foreach ($classes as $class) {
            $manyMany = $class::config()->many_many;
            foreach ($manyMany as $manyRelation => $manyType) {
                if ($manyType == $manyClass) {
                    if (!isset($related[$class])) {
                        $related[$class] = [];
                    }
                    $related[$class][] = $manyRelation;
                }
            }
        }
        return $related;
    }

    /**
     * Expand non namespaced class to the full namespaced class name
     *
     * @param string $class
     * @return string
     */
    public static function expandClass($class)
    {
        switch ($class) {
            case 'Member':
                return Member::class;
            case 'Group':
                return Group::class;
            case 'File':
                return File::class;
            case 'Image':
                return Image::class;
        }
        return $class;
    }

    /**
     * Get a class name without namespace
     *
     * @param string $class
     * @return string
     */
    public static function getClassWithoutNamespace($class)
    {
        $parts = explode("\\", $class);
        return array_pop($parts);
    }

    /**
     * Get a class name without namespace
     *
     * @param string $class
     * @return string
     */
    public static function getClassNamespace($class)
    {
        $parts = explode("\\", $class);
        array_pop($parts);
        return implode("\\", $parts);
    }

    /**
     * All dataobjects
     *
     * @return array A map of lower\case\class => Regular\Case\Class
     */
    public static function getValidDataObjects()
    {
        $list = ClassInfo::getValidSubClasses(DataObject::class);
        array_shift($list);
        return $list;
    }

    /**
     * Check if the given class is a valid data object
     *
     * @param string $class
     * @return boolean
     */
    public static function isValidDataObject($class)
    {
        $class = strtolower(str_replace('-', '\\', $class));
        $list = self::getValidDataObjects();
        return isset($list[$class]);
    }

    /**
     * Given a partial class name, attempt to determine the best module to assign strings to.
     *
     * @param string $class Either a FQN class name, or a non-qualified class name.
     * @return Module the module instance
     */
    public static function findModuleForClass($class)
    {
        $classManifest = ClassLoader::inst()->getManifest();
        return $classManifest->getOwnerModule($class);
    }

    /**
     * Find file for class
     *
     * @param string $name
     * @return string|null
     */
    public static function findFileForClass($class)
    {
        $classManifest = ClassLoader::inst()->getManifest();
        return $classManifest->getItemPath($class);
    }

    /**
     * Sanitise a model class' name for inclusion in a link
     *
     * @param string $class
     * @return string
     */
    public static function sanitiseClassName($class)
    {
        return str_replace('\\', '-', $class);
    }

    /**
     * Unsanitise a model class' name from a URL param
     *
     * @param string $class
     * @return string
     */
    public static function unsanitiseClassName($class)
    {
        return str_replace('-', '\\', $class);
    }
}
