<?php

namespace LeKoala\Base\Helpers;

use DateTime;
use DatePeriod;
use DateInterval;

class DateHelper
{
    /**
     * Get the number of days between two dates
     *
     * @param string $start
     * @param string $end
     * @return int
     */
    public static function daysDifference($start, $end)
    {
        $dStart = new DateTime($start);
        $dEnd  = new DateTime($end);
        $dDiff = $dStart->diff($dEnd);
        $diff = $dDiff->format('%r%a');
        return (int)$diff;
    }

    /**
     * Get all days between two dates
     *
     * @param string $start
     * @param string $end
     * @param string $format
     * @return array
     */
    public static function dateRange($start, $end, $format = 'Y-m-d')
    {
        if (!$start || !$end) {
            return [];
        }

        $array = [];

        // Variable that store the date interval of period 1 day
        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($end);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

        foreach ($period as $date) {
            $array[] = $date->format($format);
        }
        return $array;
    }

    /**
     * @param bool $translated
     * @return array
     */
    public static function listDays($translated = true)
    {
        if (!$translated) {
            return [
                1 => "Monday",
                2 => "Tuesday",
                3 => "Wednesday",
                4 => "Thursday",
                5 => "Friday",
                6 => "Saturday",
                7 => "Sunday",
            ];
        }
        return [
            1 => _t('DateHelper.Monday', 'Monday'),
            2 => _t('DateHelper.Tuesday', 'Tuesday'),
            3 => _t('DateHelper.Wednesday', 'Wednesday'),
            4 => _t('DateHelper.Thursday', 'Thursday'),
            5 => _t('DateHelper.Friday', 'Friday'),
            6 => _t('DateHelper.Saturday', 'Saturday'),
            7 => _t('DateHelper.Sunday', 'Sunday'),
        ];
    }

    /**
     * @param int $i
     * @param bool $translated
     * @return string
     */
    public static function dayFromIndex($i, $translated = true)
    {
        $arr = self::listDays($translated);
        return $arr[$i] ?? '';
    }
}
