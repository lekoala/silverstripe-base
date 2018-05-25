<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FieldGroup;
use LeKoala\Base\Geo\CountriesList;
use libphonenumber\PhoneNumberUtil;
use SilverStripe\ORM\DataObjectInterface;
use libphonenumber\PhoneNumberFormat;

/**
 *
 */
class CountryPhoneField extends FieldGroup
{
    public function __construct($name, $title = null, $value = null)
    {
        $source = CountriesList::get();
        $country = new CountryDropdownField($name . "[CountryCode]", "", $source);
        $country->setAttribute('style', 'max-width:166px'); // Match FieldGroup min width
        $country->setAttribute('size', 1); // fix some weird sizing issue in cms

        $number = new PhoneField($name . "[Number]", "");

        parent::__construct($title, $country, $number);

        $this->name = $name;
    }

    public function hasData()
    {
        // Turn this into a datafield
        return true;
    }

    /**
     * @return CountryDropdownField
     */
    public function getCountryField()
    {
        return $this->fieldByName($this->name . "[CountryCode]");
    }

    /**
     * @return PhoneField
     */
    public function getPhoneField()
    {
        return $this->fieldByName($this->name . "[Number]");
    }

    public function setValue($value, $data = null)
    {
        // An array of value to assign to sub fields
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->fieldByName($this->name . "[$k]")->setValue($v);
            }
            return;
        }
        // It's an international number
        if (strpos($value, '+') === 0) {
            $util = $this->getPhoneNumberUtil();
            $number = $util->parse($value);
            $regionCode = $util->getRegionCodeForNumber($number);
            $this->getCountryField()->setValue($regionCode);
            $phone = $util->format($number, PhoneNumberFormat::NATIONAL);
            $this->getPhoneField()->setValue($phone);
        } else {
            $this->getPhoneField()->setValue($value);
        }
    }

    /**
     * Value in E164 format
     *
     * @return string
     */
    public function dataValue()
    {
        $countryValue = $this->getCountryField()->Value();
        $phoneValue = $this->getPhoneField()->Value();
        if (!$phoneValue) {
            return '';
        }

        $util = $this->getPhoneNumberUtil();
        $number = $util->parse($phoneValue, $countryValue);
        return $util->format($number, PhoneNumberFormat::E164);
    }

    /**
    * @return PhoneNumberUtil
    */
    public function getPhoneNumberUtil()
    {
        return PhoneNumberUtil::getInstance();
    }
}
