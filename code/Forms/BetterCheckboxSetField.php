<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\CheckboxSetField;

/**
 * Allows setting a custom class on child elements
 *
 * Sample usage:
 * $MyCheckboxField = new BetterCheckboxSetField('MyCheckboxField', null, $src);
 * $MyCheckboxField->addExtraClass('row');
 * $MyCheckboxField->addExtraItemClass('col-md-4');
 */
class BetterCheckboxSetField extends CheckboxSetField
{
    protected $extraItemClass = [];

    public function Type()
    {
        return 'optionset checkboxset';
    }

    public function extraItemClass()
    {
        return implode(' ', array_values($this->extraItemClass));
    }

    /**
     * @param string $class
     * @return $this
     */
    public function addExtraItemClass($class)
    {
        $classes = preg_split('/\s+/', $class);

        foreach ($classes as $class) {
            $this->extraItemClass[$class] = $class;
        }

        return $this;
    }


    /**
     * @param string $class
     * @return $this
     */
    public function removeExtraItemClass($class)
    {
        $classes = preg_split('/\s+/', $class);

        foreach ($classes as $class) {
            unset($this->extraItemClass[$class]);
        }

        return $this;
    }

}
