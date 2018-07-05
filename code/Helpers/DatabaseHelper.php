<?php
namespace LeKoala\Base\Helpers;

use SqlFormatter;
use \SilverStripe\View\Parsers\SQLFormatter as SS_SQLFormatter;

class DatabaseHelper
{
    public static function formatSQL($sql)
    {
        // If we have jdorn formatter
        if (class_exists('SqlFormatter')) {
            return SqlFormatter::format($sql);
        }

        $formatter = new SS_SQLFormatter;
        return $formatter->formatHTML($sql);
    }
}
