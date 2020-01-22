<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBCurrency;
use LeKoala\Base\Forms\InputMaskCurrencyField;
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
        $field = new InputMaskCurrencyField($this->name, $title);
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
