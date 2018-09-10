<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Control\Controller;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\ORM\DataObject;

/**
 * Allows setting a custom class on child elements
 *
 * Sample usage:
 * $MyCheckboxField = new BetterCheckboxSetField('MyCheckboxField', null, $src);
 * $MyCheckboxField->addExtraClass('row');
 * $MyCheckboxField->addExtraItemClass('col-md-4');
 *
 * Support legacy encoding (comma vs json)
 */
class BetterCheckboxSetField extends CheckboxSetField
{
    const LEGACY_SEPARATOR = ',';

    /**
     * @var array
     */
    protected $extraItemClass = [];

    public function __construct($name, $title = null, $source = array(), $value = null)
    {
        parent::__construct($name, $title, $source, $value);

        $isCMS = Controller::curr() instanceof LeftAndMain;
        if ($isCMS) {
            $this->template = 'LeKoala\\Base\\Forms\\CMSBetterCheckboxSetField';
        } else {
            $this->template = 'LeKoala\\Base\\Forms\\BetterCheckboxSetField';
        }
    }

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

    public function setValue($value, $obj = null)
    {
        // We have an array for instance, we still need proper decoding
        if ($obj && !$obj instanceof DataObject) {
            $value = $this->stringDecode($value);
        }
        return parent::setValue($value, $obj);
    }

    /**
     * Extract a string value into an array of values
     *
     * @param string $value
     * @return array
     */
    protected function stringDecode($value)
    {
        // Handle empty case
        if (empty($value)) {
            return array();
        }
        // Already converted
        if (is_array($value)) {
            return $value;
        }

        // We have a json encoded array
        if (strpos($value, '[') === 0) {
            $result = json_decode($value, true);
        } else {
            $result = explode(self::LEGACY_SEPARATOR, $value);
        }

        if ($result !== false) {
            return $result;
        }

        throw new InvalidArgumentException("Could not decode : $value");
    }
}
