<?php
namespace LeKoala\Base\Helpers;

use SqlFormatter;
use \SilverStripe\View\Parsers\SQLFormatter as SS_SQLFormatter;

class DatabaseHelper
{
    /**
     * Format sql using built in formatter or custom one
     *
     * @param string $sql
     * @return string The formatted string
     */
    public static function formatSQL(string $sql)
    {
        // If we have jdorn formatter
        if (class_exists('SqlFormatter')) {
            return SqlFormatter::format($sql);
        }

        $formatter = new SS_SQLFormatter;
        return $formatter->formatHTML($sql);
    }
}
