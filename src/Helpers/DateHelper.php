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
     * @return array
     */
    public static function listDays()
    {
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
}
