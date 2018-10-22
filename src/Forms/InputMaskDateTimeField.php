<?php
namespace LeKoala\Base\Forms;

/**
 * Format date field using ISO value
 *
 * Serves as a base field for all date field since we need datetime alias
 *
 * Locale conversion cannot be done by InputMask and should be provided by a third party service
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
        return parent::setValue($value, $data);
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
