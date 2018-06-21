<?php
namespace LeKoala\Base\Forms;

use NumberFormatter;
use SilverStripe\ORM\FieldType\DBCurrency;

/**
 * Format currency
 */
class InputMaskCurrencyField extends InputMaskField
{
    /**
     * a char(3)
     *
     * @var string
     */
    protected $currency;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);

        $this->setAlias(self::ALIAS_CURRENCY);
        $this->setRighAlign(false);
        $this->setPrefix($this->getSymbol() . ' ');
        $patternInfos = $this->parsePattern();
        $this->setGroupSeparator($patternInfos['group']);
        $this->setRadixPoint($patternInfos['decimal']);
    }

    /**
     * A naive pattern parser
     *
     * @return array an array with two keys
     */
    protected function parsePattern()
    {
        $pattern = $this->getFormatter()->getPattern();
        $matches = null;
        preg_match_all('/#(?P<group>,|\.).*0(?P<decimal>,|\.)/', $pattern, $matches);
        return [
            'group' => $matches['group'][0] ?? ',',
            'decimal' => $matches['decimal'][0] ?? '.',
        ];
    }

    public function setValue($value, $data = null)
    {
        return parent::setValue($value, $data);
    }


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
     * Get the value of currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set the value of currency
     *
     * @return  self
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->getFormatter()->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Get default symbol
     *
     * @return string
     */
    public function getDefaultSymbol()
    {
        return DBCurrency::config()->uninherited('currency_symbol');
    }

    /**
     * Create a new class for this field
     */
    public function performReadonlyTransformation()
    {
        return $this->castedCopy('SilverStripe\\Forms\\CurrencyField_Readonly');
    }
}
