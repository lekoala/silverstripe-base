<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\TextField;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

/**
 * A simple phone field
 *
 * Formatting only works with international number because we don't know the country
 *
 * For national numbers, use CountryPhoneField that use a combination of CountryCode + PhoneNumber field
 */
class PhoneField extends TextField
{
    public function getInputType()
    {
        return 'phone';
    }

    public function Type()
    {
        return 'text';
    }

    public function setValue($value, $data = null)
    {
        // We have an international number that we can format easily
        // without knowing the country
        if (strpos($value, '+') === 0) {
            $util = $this->getPhoneNumberUtil();
            try {
                $number = $util->parse($value);
                $newValue = $util->format($number, PhoneNumberFormat::INTERNATIONAL);
            } catch (NumberParseException $ex) {
                $newValue = $value;
            }
            $value = $newValue;

        }
        return parent::setValue($value, $data);
    }

    /**
     * Value in E164 format (no formatting)
     *
     * @return string
     */
    public function dataValue()
    {
        $value = $this->Value();
        if (strpos($value, '+') === 0) {
            $util = $this->getPhoneNumberUtil();
            try {
                $number = $util->parse($value);
                $formatted = $util->format($number, PhoneNumberFormat::E164);
            } catch (NumberParseException $ex) {
                $formatted = $value;
            }
            return $formatted;
        }
        return $value;
    }

    /**
     * @return PhoneNumberUtil
     */
    public function getPhoneNumberUtil()
    {
        return PhoneNumberUtil::getInstance();
    }
}
