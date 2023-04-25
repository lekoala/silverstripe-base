<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

/**
 * Format input using cleave.js
 *
 * @link https://nosir.github.io/cleave.js/
 * @link https://github.com/lekoala/cleave-es6
 */
class CleaveField extends TextField
{
    use ConfigurableField;

    protected $inputType;

    /**
     * @config
     * @var array
     */
    private static $default_config = [
        "swapHiddenInput" => true,
    ];

    public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null)
    {
        parent::__construct($name, $title, $value, $maxLength, $form);
        $this->mergeDefaultConfig();
    }

    public function Type()
    {
        return 'cleave';
    }

    public function extraClass()
    {
        return 'text ' . parent::extraClass();
    }

    public function Field($properties = array())
    {
        $properties['InputType'] = $this->inputType;
        self::requirements();
        return parent::Field($properties);
    }

    public static function requirements()
    {
        Requirements::javascript("lekoala/silverstripe-base: javascript/custom-elements/cleave-input.min.js");
    }

    /**
     * Get the value of inputType
     * @return string
     */
    public function getInputType()
    {
        return $this->inputType;
    }

    /**
     * Set the value of inputType
     *
     * @param string $inputType date,time,datetime,numeral
     * @return $this
     */
    public function setInputType($inputType)
    {
        $this->inputType = $inputType;
        return $this;
    }

    public function getDigits()
    {
        return $this->getConfig('numeralDecimalScale');
    }

    public function setDigits($v)
    {
        return $this->setConfig('numeralDecimalScale', $v);
    }

    public function getRadixPoint()
    {
        return $this->getConfig('numeralDecimalMark');
    }

    public function setRadixPoint($v)
    {
        return $this->setConfig('numeralDecimalMark', $v);
    }

    public function getGroupSeparator()
    {
        return $this->getConfig('delimiter');
    }

    public function setGroupSeparator($value)
    {
        return $this->setConfig('delimiter', $value);
    }

    public function getEnforceDigitsOnBlur()
    {
        return $this->getConfig('numeralDecimalPadding');
    }

    public function setEnforceDigitsOnBlur($value)
    {
        return $this->setConfig('numeralDecimalPadding ', $value);
    }

    public function getPrefix()
    {
        return $this->getConfig('prefix');
    }

    public function setPrefix($value)
    {
        return $this->setConfig('prefix', $value);
    }
}
