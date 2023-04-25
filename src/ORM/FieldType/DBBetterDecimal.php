<?php

namespace LeKoala\Base\ORM\FieldType;

use LeKoala\Base\Forms\CleaveField;
use SilverStripe\ORM\FieldType\DBDecimal;
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
     * @return CleaveField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = new CleaveField($this->name, $title);
        $field->setInputType('numeral');
        $field->setDigits($this->decimalSize);
        $field->setRadixPoint($this->getCurrencyDecimalSeparator());
        $field->setGroupSeparator($this->getCurrencyGroupingSeparator());
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
