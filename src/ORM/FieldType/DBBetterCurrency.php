<?php

namespace LeKoala\Base\ORM\FieldType;

use LeKoala\FormElements\CleaveField;
use SilverStripe\ORM\FieldType\DBCurrency;
use LeKoala\Base\Helpers\CurrencyFormatter;

/**
 * Improve currency field for usage with other locales
 */
class DBBetterCurrency extends DBCurrency
{
    use CurrencyFormatter;

    private static $casting = [
        "HTMLAmount" => "HTMLFragment"
    ];

    /**
     * Returns the number as a currency, eg “$1,000.00”.
     *
     * @return string
     */
    public function Nice()
    {
        return $this->Amount();
    }

    /**
     * @param string $title
     * @param array $params
     *
     * @return NumericField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = new CleaveField($this->name, $title);
        $field->setCleaveType('numeral');
        $field->setDigits($this->decimalSize);
        $field->setRadixPoint($this->getCurrencyDecimalSeparator());
        $field->setGroupSeparator($this->getCurrencyGroupingSeparator());
        // there is no space between the dollar symbol and the amount for US currency.
        // on the other hand, there is a non-breaking space between the dollar amount and the euro sign for EU countries.
        // $field->setPrefix($this->getCurrencySymbol());
        return $field;
    }

    /**
     * Format the amount
     *
     * @param integer $decimals
     * @return string
     */
    public function Amount($decimals = 2)
    {
        return $this->formattedCurrency($this->value, $decimals);
    }

    /**
     * Format the amount
     *
     * @param integer $decimals
     * @return string
     */
    public function Decimals($decimals = 2)
    {
        return $this->formattedNumber($this->value, $decimals);
    }

    /**
     * Return an html friendly version of the amount without breaking spaces
     *
     * @param integer $decimals
     * @return string
     */
    public function HTMLAmount($decimals = 2)
    {
        return str_replace(' ', '&nbsp;', $this->Amount($decimals));
    }
}
