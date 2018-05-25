<?php
namespace LeKoala\Base\ORM\FieldType;

use LeKoala\Base\Forms\PhoneField;
use libphonenumber\PhoneNumberUtil;
use LeKoala\Base\Forms\CountryPhoneField;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBVarchar;
use Prophecy\Exception\InvalidArgumentException;

/**
 * Phone field type
 *
 * @see https://github.com/giggsey/libphonenumber-for-php
 */
class DBPhone extends DBVarchar
{
    const FORMAT_E164 = "E164";
    const FORMAT_INTERNATIONAL = "INTERNATIONAL";
    const FORMAT_NATIONAL = "NATIONAL";
    const FORMAT_RFC3966 = "RFC3966";

    /**
     * @var array
     */
    protected static $valid_formats = [
        'E164',
        'INTERNATIONAL',
        'NATIONAL',
        'RFC3966'
    ];

    public function __construct($name = null, $options = [])
    {
        // E164 specify it should be smaller than 15 chars
        parent::__construct($name, 16, $options);
    }

    public function saveInto($dataObject)
    {
        $fieldName = $this->name;
        $country = null;
        if ($dataObject->CountryCode) {
            $country = $dataObject->CountryCode;
        }
        $dataObject->$fieldName = $this->parseNumber($this->value);
        l([$this->value, $dataObject->$fieldName, $dataObject->CountryCode, $country]);
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        return parent::setValue($value, $record, $markChanged);
    }

    public function dataValue()
    {
        return $this->value;
    }

    /**
    * If the number is passed in an international format (e.g. +44 117 496 0123), then the region code is not needed, and can be null.
    * Failing that, the library will use the region code to work out the phone number based on rules loaded for that region.
    *
    * @param mixed $value
    * @param string $country
    * @return string|null|false Formatted number, null if empty but valid, or false if invalid
    */
    protected function parseNumber($value, $country = null)
    {
        // Skip empty values
        if (empty($value)) {
            return null;
        }

        // It's an international number, let the parser define the country
        if (strpos($value, '+') === 0) {
            $country = null;
        } else {
            // If no country and not international number, return value as is
            if (!$country) {
                return $value;
            }
            $country = strtoupper($country);
        }

        $phoneUtil = $this->getPhoneNumberUtil();
        $number = $phoneUtil->parse($value, $country);
        $formattedValue = $phoneUtil->format($number, self::FORMAT_INTERNATIONAL);
        return $formattedValue;
    }


    public function scaffoldFormField($title = null, $params = null)
    {
        $field = PhoneField::create($this->name, $title);
        return $field;
    }

    /**
     * @return PhoneNumberUtil
     */
    public function getPhoneNumberUtil()
    {
        return PhoneNumberUtil::getInstance();
    }

    /**
    * Return the date using a particular formatting string. Use {o} to include an ordinal representation
    * for the day of the month ("1st", "2nd", "3rd" etc)
    *
    * @param string $format Format code string. See http://userguide.icu-project.org/formatparse/datetime
    * @return string The date in the requested format
    */
    public function Format($format = null)
    {
        if (!$this->value) {
            return null;
        }

        if (!$format) {
            $format = self::FORMAT_INTERNATIONAL;
        }
        if (!in_array($format, self::$valid_formats)) {
            throw new InvalidArgumentException("Format $format is invalid");
        }

        $phoneUtil = $this->getPhoneNumberUtil();
        $number = $phoneUtil->parse($this->value);
        return $phoneUtil->format($number, $format);
    }

    public function E164()
    {
        return $this->Format(self::FORMAT_E164);
    }

    public function International()
    {
        return $this->Format(self::FORMAT_INTERNATIONAL);
    }

    public function National()
    {
        return $this->Format(self::FORMAT_NATIONAL);
    }

    public function Rfc3966()
    {
        return $this->Format(self::FORMAT_RFC3966);
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return self::validatePhoneNumber($this->value);
    }

    /**
      * @param string $value
      * @param string $country
      * @param string $format
      * @return bool
      */
    public static function validatePhoneNumber($value, $country = null)
    {
        $phoneUtil = $this->getPhoneNumberUtil();

        // It's an international number, let the parser define the country
        if (strpos($value, '+') === 0) {
            $country = null;
        }

        $number = $phoneUtil->parse($value, $country);
        return $phoneUtil->isValidNumber($number);
    }
}
