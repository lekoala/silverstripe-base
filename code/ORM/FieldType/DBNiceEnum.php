<?php
namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\FieldType\DBEnum;

/**
 * A smarter enum that allows you to feed values from a method
 * This method is expected to return a static array with key=>values pairs
 */
class DBNiceEnum extends DBEnum
{
    protected $class;

    public function __construct($name = null, $enum = null, $default = 0, $options = [])
    {
        // We can declare a class that will look for class::listNameOfField method
        if ($enum && is_string($enum) && class_exists($enum)) {
            $this->class = $enum;
            $this->name = $name;
            $enum = array_keys($this->toArray());
        }
        parent::__construct($name, $enum, $default, $options);
    }

    /**
     * Return a dropdown field suitable for editing this field.
     *
     * @param string $title
     * @param string $name
     * @param bool $hasEmpty
     * @param string $value
     * @param string $emptyString
     * @return DropdownField
     */
    public function formField($title = null, $name = null, $hasEmpty = false, $value = "", $emptyString = null)
    {

        if (!$title) {
            $title = $this->getName();
        }
        if (!$name) {
            $name = $this->getName();
        }

        $field = new DropdownField($name, $title, $this->toArray(), $value);
        if ($hasEmpty) {
            $field->setEmptyString($emptyString);
        }

        return $field;
    }

    public function toArray()
    {
        $method = 'list' . ucfirst($this->name);
        $class = $this->class;
        return $class::$method();
    }

    public function Nice()
    {
        $arr = $this->toArray();
        $val = $this->getValue();
        if (isset($arr[$val])) {
            return $arr[$val];
        }
        return $val;
    }
}
