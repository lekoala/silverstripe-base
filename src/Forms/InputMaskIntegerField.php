<?php
namespace LeKoala\Base\Forms;

/**
 * Format numbers
 */
class InputMaskIntegerField extends InputMaskNumericField
{
    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $this->setAlias(self::ALIAS_INTEGER);
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
        // $field = $this->castedCopy('SilverStripe\\Forms\\NumericField');
        // $field->setReadonly(true);
        $field = $this->castedCopy(NumericReadonlyField::class);
        return $field;
    }
}
