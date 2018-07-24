<?php
namespace LeKoala\Base\Forms;

use LeKoala\Base\Helpers\CurrencyFormatter;

/**
 * Format numbers
 */
class InputMaskIntegerField extends InputMaskField
{
    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $this->setAlias(self::ALIAS_INTEGER);
        $this->setRighAlign(false);
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
