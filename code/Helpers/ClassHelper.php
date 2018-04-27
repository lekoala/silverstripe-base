<?php
namespace LeKoala\Base\Helpers;

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
        if(is_object($class)) {
            $class = get_class($class);
        }
        if (\strpos($class, '\\') === false) {
            return $class;
        }
        return substr(strrchr($class, '\\'), 1);
    }

}
