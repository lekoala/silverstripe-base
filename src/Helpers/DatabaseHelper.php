<?php

namespace LeKoala\Base\Helpers;

use Exception;
use SqlFormatter;
use SilverStripe\ORM\DB;
use \SilverStripe\View\Parsers\SQLFormatter as SS_SQLFormatter;

/**
 * Helps dealing with database
 */
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

    /**
     * Returns an order by clause with an order based on the list of fields
     *
     * @param string $field The field name
     * @param array $list The list of values
     * @return string
     */
    public static function orderByField($field, $list)
    {
        $orderBy = "ORDER BY CASE $field";
        $i = 1;
        foreach ($list as $k) {
            $orderBy .= " WHEN '$k' THEN $i";
            $i++;
        }
        $orderBy .= " ELSE $i";
        $orderBy .= " END";
        return $orderBy;
    }

    /**
     * Wrap db call in a transaction
     *
     * @param callable $callback
     * @return void
     */
    public static function transaction($callback)
    {
        $conn = DB::get_conn();
        $conn->transactionStart();
        try {
            $callback();
        } catch (Exception $ex) {
            $conn->transactionRollback();
        }
        $conn->transactionEnd();
    }
}
