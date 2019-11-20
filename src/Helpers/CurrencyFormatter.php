<?php

namespace LeKoala\Base\Helpers;

use NumberFormatter;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBCurrency;

trait CurrencyFormatter
{

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * a char(3) currency code
     *
     * @var string
     */
    protected $currency;

    /**
     * Get currency formatter
     *
     * @return NumberFormatter
     */
    public function getFormatter()
    {
        $locale = $this->getLocale();
        $currency = $this->getCurrency();
        if ($currency) {
            $locale .= '@currency=' . $currency;
        }
        return NumberFormatter::create($locale, NumberFormatter::CURRENCY);
    }

    /**
     * Get locale to use for this field
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Get the currency code
     *
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * The currency code (eg: USD, EUR...)
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency symbol
     *
     * This is only returned if currency is explicitely set to avoid returning the wrong currency
     * just because of user's locale
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        if ($this->currency) {
            return $this->getFormatter()->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
        }
        return $this->getDefaultCurrencySymbol();
    }

    /**
     * Get default symbol
     *
     * @return string
     */
    public function getDefaultCurrencySymbol()
    {
        return DBCurrency::config()->uninherited('currency_symbol');
    }

    /**
     * Get default symbol
     *
     * @return string
     */
    public function getDefaultCurrencyPosition()
    {
        return DBCurrency::config()->uninherited('currency_position');
    }

    /**
     * Get grouping separator
     *
     * @return strubg
     */
    public function getCurrencyGroupingSeparator()
    {
        return $this->getFormatter()->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    /**
     * Get decimal separator
     *
     * @return string
     */
    public function getCurrencyDecimalSeparator()
    {
        return $this->getFormatter()->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    /**
     * Get nicely formatted currency (based on current locale)
     *
     * @param $amount
     * @return string
     */
    public function formattedCurrency($amount, $decimals = 2)
    {
        $negative = false;
        if ($amount < 0) {
            $negative = true;
        }

        $currency = $this->getCurrency();

        // Without currency, format as basic localised number
        // Otherwise we could end up displaying dollars in euros due to current locale
        // We only format according to the locale if the currency is set
        if (!$currency) {
            $symbol = $this->getCurrencySymbol();
            $pos = $this->getDefaultCurrencyPosition();
            $fmt =  number_format(abs($amount), $decimals, $this->getCurrencyDecimalSeparator(), $this->getCurrencyGroupingSeparator());;
            if ($pos == 'after') {
                $ret = "$fmt $symbol";
            } else {
                $ret = "$symbol $fmt";
            }
        } else {
            $formatter = $this->getFormatter();
            $ret = $formatter->formatCurrency($amount, $currency);
        }

        if ($negative) {
            $ret = "-$ret";
        }

        return $ret;
    }

    /**
     * @link https://github.com/mcuadros/currency-detector/blob/master/src/CurrencyDetector/Detector.php
     * @param string|array $value
     */
    public static function unformatCurrency($value)
    {
        // If we pass an array of values, apply to the whole array
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = self::unformatCurrency($val);
            }
            return $value;
        }

        // Return early
        if (!$value) {
            return 0;
        }

        // If it contains -, it's a negative number
        $neg = false;
        if (strpos($value, '-') !== false) {
            $neg = true;
        }

        // Remove all separators except the last one
        $cleanString = preg_replace('/([^0-9\.,])/i', '', $value);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $value);
        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;
        $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);

        // Remove any thousand separator followed by 3 digits before the end of the string
        $removeThousandsSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '', $stringWithCommaOrDot);

        $value = str_replace(',', '.', $removeThousandsSeparator);

        // For a negative value, return it as such
        if ($neg) {
            $value = -1 * abs($value);
        }
        return $value;
    }
}
