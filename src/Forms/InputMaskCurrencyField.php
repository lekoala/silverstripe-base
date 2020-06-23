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
        // otherwise values like 84.4 will be interpreted as 844.00
        if (is_float($value) && strlen($value)) {
            $value = number_format($value, 2, $this->getCurrencyDecimalSeparator(), "");
            // $value = $this->formattedCurrency($value);
        }
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
