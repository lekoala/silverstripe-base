<?php

namespace LeKoala\Base\ORM\FieldType;

use Exception;
use InvalidArgumentException;
use SilverStripe\ORM\FieldType\DBDate;

/**
 * This one does not crash in case your database contains rubbish data
 */
class DBBetterDate extends DBDate
{
    /**
     * Fix non-iso dates
     *
     * @param string $value
     * @return string
     */
    protected function fixInputDate($value)
    {
        try {
            // split
            list($year, $month, $day, $time) = $this->explodeDateString($value);
        } catch (Exception $ex) {
            $year = 0;
            $month = 0;
            $day = 0;
        }

        if ((int) $year === 0 && (int) $month === 0 && (int) $day === 0) {
            return null;
        }
        // Validate date
        if (!checkdate($month, $day, $year)) {
            throw new InvalidArgumentException(
                "Invalid date: '$value'. Use " . self::ISO_DATE . " to prevent this error."
            );
        }

        // Convert to y-m-d
        return sprintf('%d-%02d-%02d%s', $year, $month, $day, $time);
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
