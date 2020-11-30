<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DataObjectInterface;

/**
 * Format time field
 */
class CleaveTimeField extends CleaveField
{
    /**
     * Set this to true if internal value is seconds
     *
     * @var boolean
     */
    protected $isNumeric = false;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);

        $this->setConfig('time', true);
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

    public function setValue($value, $data = null)
    {
        if ($this->isNumeric && is_numeric($value)) {
            $old = $value;
            $value = self::secondsToTime($value);
        }
        // Don't call parent that can set locale formatted date
        $this->value = $value;
        return $this;
    }

    public function dataValue()
    {
        $value = parent::dataValue();
        // Value is stored in database in seconds
        if ($this->isNumeric) {
            return self::timeToSeconds($value);
        }
        return $value;
    }

    public function saveInto(DataObjectInterface $record)
    {
        return parent::saveInto($record);
    }

    /**
     * Get the value of isNumeric
     * @return mixed
     */
    public function getIsNumeric()
    {
        return $this->isNumeric;
    }

    /**
     * Set the value of isNumeric
     *
     * @param mixed $isNumeric
     * @return $this
     */
    public function setIsNumeric($isNumeric)
    {
        $this->isNumeric = $isNumeric;
        return $this;
    }
}
