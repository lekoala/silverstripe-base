<?php
namespace LeKoala\Base\Forms;

/**
 * Format numbers
 */
class InputMaskPercentageField extends InputMaskNumericField
{
    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $this->setAlias(self::ALIAS_PERCENTAGE);
        $this->setMax('');
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
