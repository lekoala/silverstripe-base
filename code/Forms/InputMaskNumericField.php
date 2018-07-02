<?php
namespace LeKoala\Base\Forms;

use LeKoala\Base\Helpers\CurrencyFormatter;

/**
 * Format numbers
 */
class InputMaskNumericField extends InputMaskField
{
    use CurrencyFormatter;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $this->setAlias(self::ALIAS_NUMERIC);
        $this->applyDefaultNumericOptions();
    }

    public function applyDefaultNumericOptions()
    {
        $this->setRighAlign(false);
        $this->setAutogroup(true);
        $this->setGroupSeparator($this->getCurrencyGroupingSeparator());
        $this->setRadixPoint($this->getCurrencyDecimalSeparator());
    }

    public function setValue($value, $data = null)
    {
        return parent::setValue($value, $data);
    }

    /**
     * Create a new class for this field
     */
    public function performReadonlyTransformation()
    {
        $field = $this->castedCopy('SilverStripe\\Forms\\NumericField');
        $field->setReadonly(true);
        return $field;
    }
}
