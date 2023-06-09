<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

/**
 * Format input using cleave.js
 *
 * @link https://nosir.github.io/cleave.js/
 * @link https://github.com/lekoala/cleave-es6
 * @deprecated use FormElements
 */
class CleaveField extends TextField
{
    use ConfigurableField;

    protected $cleaveType;

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
        self::requirements();

        $html = parent::Field($properties);
        $config = $this->getConfigAsJson();

        $type = '';
        if ($this->getCleaveType()) {
            $type = ' type="' . $this->getCleaveType() . '"';
        }

        // Simply wrap with custom element and set config
        $html = "<cleave-input data-config='" . $config . "'" . $type . ">" . $html . '</cleave-input>';

        return $html;
    }

    public static function requirements()
    {
        Requirements::javascript("lekoala/silverstripe-base: javascript/custom-elements/cleave-input.min.js");
    }

    /**
     * Get the value of cleaveType
     * @return string
     */
    public function getCleaveType()
    {
        return $this->cleaveType;
    }

    /**
     * Set the value of inputType
     *
     * @param string $cleaveType date,time,datetime,numeral
     * @return $this
     */
    public function setCleaveType($cleaveType)
    {
        $this->cleaveType = $cleaveType;
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
