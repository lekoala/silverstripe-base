<?php
namespace LeKoala\Base\Forms;

use LeKoala\Base\Helpers\CurrencyFormatter;

/**
 * Format currency
 */
class InputMaskCurrencyField extends InputMaskNumericField
{
    use CurrencyFormatter;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $this->setAlias(self::ALIAS_CURRENCY);
        $this->setPrefix($this->getCurrencySymbol() . ' ');
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
        return $this->castedCopy(InputMaskCurrencyField_Readonly::class);
    }
}
