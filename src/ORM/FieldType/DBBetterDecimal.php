<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBDecimal;
use LeKoala\Base\Forms\InputMaskNumericField;
use LeKoala\Base\Helpers\CurrencyFormatter;

/**
 * Improve currency field for usage with other locales
 */
class DBBetterDecimal extends DBDecimal
{
    use CurrencyFormatter;

    /**
     * @param string $title
     * @param array $params
     *
     * @return InputMaskDecimalField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = new InputMaskNumericField($this->name, $title);
        $field->setDigits($this->decimalSize);
        if (!$this->decimalSize) {
            $field->setRadixPoint("");
        }
        return $field;
    }

    /**
     * @return float
     */
    public function Nice()
    {
        return number_format($this->value, $this->decimalSize, $this->getCurrencyDecimalSeparator(), $this->getCurrencyGroupingSeparator());
    }
}
