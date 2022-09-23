<?php

namespace LeKoala\Base\ORM\FieldType;

use DateTime;
use DateTimeZone;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Play with timezone
 */
class DBBetterDateTime extends DBDatetime
{

    /**
     * Timestamp is always UTC
     *
     * So we add the new offset and remove our default offset to get the current
     * value
     *
     * @param string $timezone
     * @return int The offseted timestamp that can be formatted with Y-m-d H:i:s without worries
     */
    public function toTimezone($timezone = 'UTC')
    {
        if (!$this->value) {
            return null;
        }
        $timestamp = strtotime($this->value);
        $userTimezone = new DateTimeZone($timezone);
        $defaultTimezone = new DateTimeZone(date_default_timezone_get());
        $defaultTime = new DateTime('now');
        $defaultOffset = $defaultTimezone->getOffset($defaultTime);
        $offset = $userTimezone->getOffset($defaultTime);
        return $timestamp + $offset - $defaultOffset;
    }

    /**
     * @return string
     */
    public function UcMonth()
    {
        return ucfirst($this->Month());
    }

    /**
     * @return string
     */
    public function ShortMonthNoDot()
    {
        return trim($this->ShortMonth(), '.');
    }

    /**
     * Returns the year from the given date
     *
     * @return string
     */
    public function ShortYear()
    {
        return $this->Format('yy');
    }
}
