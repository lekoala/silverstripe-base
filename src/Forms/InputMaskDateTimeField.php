<?php
namespace LeKoala\Base\Forms;

use SilverStripe\i18n\i18n;

/**
 * Format date field using ISO value
 *
 * Serves as a base field for all date field since we need datetime alias
 *
 * Locale conversion cannot be done by InputMask and should be provided by a third party service
 *
 * @link https://github.com/RobinHerbots/Inputmask/blob/5.x/README_date.md
 */
class InputMaskDateTimeField extends InputMaskField
{
    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);

        $this->setAlias(self::ALIAS_DATETIME);
    }

    public function setValue($value, $data = null)
    {
        // Normalize input value according to our format
        if ($value) {
            $value = date((self::convertDateFormatToPhp(self::getDefaultDateFormat())) . ' H:i:s', strtotime($value));
        }
        $this->value = $value;
        return $this;
    }

    /**
     * Get the input format for inputmask
     * @return string
     */
    public static function getDefaultDateFormat()
    {
        $config = self::config()->get('default_input_format');
        if (!$config || $config == 'auto') {
            $locale = strtolower(substr(i18n::get_locale(), 0, 2));
            switch ($locale) {
                case 'fr':
                case 'nl':
                    return 'dd/mm/yyyy';
                    break;
                default:
                    return 'yyyy-mm-dd';
            }
        }
        return $config;
    }

    /**
     * @param string $format
     * @return string
     */
    public static function convertDateFormatToPhp($format)
    {
        $format = str_replace('yyyy', 'Y', $format);
        $format = str_replace('mm', 'm', $format);
        $format = str_replace('dd', 'd', $format);
        return $format;
    }

    /**
     * Format used to input the date
     *
     * @return string
     */
    public function getInputFormat()
    {
        return $this->getConfig('inputFormat');
    }

    public function setInputFormat($value)
    {
        return $this->setConfig('inputFormat', $value);
    }

    /**
     * Unmasking format
     *
     * @return string
     */
    public function getOutputFormat()
    {
        return $this->getConfig('outputFormat');
    }


    public function setOutputFormat($value)
    {
        return $this->setConfig('outputFormat', $value);
    }

    /**
     * Visual format when the input looses focus
     *
     * @return string
     */
    public function getDisplayFormat()
    {
        return $this->getConfig('displayFormat');
    }

    public function setDisplayFormat($value)
    {
        return $this->setConfig('displayFormat', $value);
    }

    /**
     * @param int $seconds
     * @return string
     */
    public static function secondsToTime($seconds)
    {
        $t = round($seconds);
        return sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);
    }

    /**
     * @param string $time
     * @return int
     */
    public static function timeToSeconds($time)
    {
        sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
        $result = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
        return (int)$result;
    }
}
