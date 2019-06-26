<?php
namespace LeKoala\Base\Forms;

use LeKoala\Base\Helpers\CurrencyFormatter;
use SilverStripe\Forms\ReadonlyField;

/**
 * Format numbers
 *
 * Use CurrencyFormatter to get rules for decimals and grouping separators
 *
 * @link https://github.com/RobinHerbots/Inputmask/blob/4.x/README_numeric.md
 */
class InputMaskNumericField extends InputMaskField
{
    use CurrencyFormatter;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $this->setAlias(self::ALIAS_NUMERIC);
        $this->applyDefaultNumericOptions();
    }

    public function applyDefaultNumericOptions()
    {
        $this->setRighAlign(false);
        $this->setAutogroup(true);
        $this->setGroupSeparator($this->getCurrencyGroupingSeparator());
        $this->setRadixPoint($this->getCurrencyDecimalSeparator());
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

    public function getDigits()
    {
        return $this->getConfig('digits');
    }

    public function setDigits($value)
    {
        return $this->setConfig('digits', $value);
    }

    public function getDigitsOptional()
    {
        return $this->getConfig('digitsOptional');
    }

    public function setDigitsOptional($value)
    {
        return $this->setConfig('digitsOptional', $value);
    }

    public function getEnforceDigitsOnBlur()
    {
        return $this->getConfig('enforceDigitsOnBlur');
    }

    public function setEnforceDigitsOnBlur($value)
    {
        return $this->setConfig('enforceDigitsOnBlur', $value);
    }

    public function getGroupSize()
    {
        return $this->getConfig('groupSize');
    }

    public function setGroupSize($value)
    {
        return $this->setConfig('groupSize', $value);
    }

    public function getAutogroup()
    {
        return $this->getConfig('autoGroup');
    }

    public function setAutogroup($value)
    {
        return $this->setConfig('autoGroup', $value);
    }

    public function getAllowMinus()
    {
        return $this->getConfig('allowMinus');
    }

    public function setAllowMinus($value)
    {
        return $this->setConfig('allowMinus', $value);
    }

    public function getNegationSymbol()
    {
        return $this->getConfig('negationSymbol');
    }

    public function setNegationSymbol($value)
    {
        return $this->setConfig('negationSymbol', $value);
    }

    public function getIntegerDigits()
    {
        return $this->getConfig('integerDigits');
    }

    public function setIntegerDigits($value)
    {
        return $this->setConfig('integerDigits', $value);
    }

    public function getIntegerOptional()
    {
        return $this->getConfig('integerOptional');
    }

    public function setIntegerOptional($value)
    {
        return $this->setConfig('integerOptional', $value);
    }

    public function getPrefix()
    {
        return $this->getConfig('prefix');
    }

    public function setPrefix($value)
    {
        return $this->setConfig('prefix', $value);
    }

    public function getSuffix()
    {
        return $this->getConfig('suffix');
    }

    public function setSuffix($value)
    {
        return $this->setConfig('suffix', $value);
    }

    public function getDecimalProtect()
    {
        return $this->getConfig('decimalProtect');
    }

    public function setDecimalProtect($value)
    {
        return $this->setConfig('decimalProtect', $value);
    }

    public function getMin()
    {
        return $this->getConfig('min');
    }

    public function setMin($value)
    {
        return $this->setConfig('min', $value);
    }

    public function getMax()
    {
        return $this->getConfig('max');
    }

    public function setMax($value)
    {
        return $this->setConfig('max', $value);
    }
}
