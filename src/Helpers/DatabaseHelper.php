<?php

namespace LeKoala\Base\Helpers;

use Exception;
use SqlFormatter;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\SQLite\SQLite3Database;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\Queries\SQLInsert;
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

    /**
     * Get current database type
     *
     * @return string mysql|sqlite
     */
    public static function getDbType()
    {
        $conn = DB::get_conn();
        if ($conn instanceof MySQLDatabase) {
            return 'mysql';
        } elseif ($conn instanceof SQLite3Database) {
            return 'sqlite';
        }

        throw new Exception("Unsupported db type : " . get_class($conn));
    }

    /**
     * @param string $date The quoted date value, defaults to now
     * @return string
     */
    public static function unixTimestampFunc($date = null)
    {
        switch (self::getDbType()) {
            case 'sqlite':
                if (!$date) {
                    $date = "'now'";
                }
                // @link https://www.techonthenet.com/sqlite/functions/now.php
                return "strftime('%s', $date)";
            case 'mysql':
                // @link https://www.w3resource.com/mysql/date-and-time-functions/mysql-unix_timestamp-function.php
                return "UNIX_TIMESTAMP($date)";
        }
    }

    /**
     * @param array $values An array of properly quoted values or unquoted column names or function
     * @return string
     */
    public static function concat($values)
    {
        switch (self::getDbType()) {
            case 'sqlite':
                // @link https://www.sqlitetutorial.net/sqlite-string-functions/sqlite-concat/
                return implode("||", $values);
            case 'mysql':
                return "CONCAT(" . implode(',', $values) . ")";
        }
    }

    /**
     * Mutate where array with in clause
     *
     * @param array $arr
     * @param string $field
     * @param array|string $values
     * @return void
     */
    public static function inArray(&$arr, $field, $values)
    {
        if (empty($values)) {
            return;
        }
        if (is_string($values)) {
            $arr["$field = ?"] = $values;
        } else {
            $params = [];
            foreach ($values as $v) {
                $params[] = '?';
            }
            $paramsStr = implode(",", $params);
            $arr["$field IN ($paramsStr)"] = $values;
        }
    }

    /**
     * @param string $table
     * @param string $column
     * @return array
     */
    public static function findDuplicates($table, $column, $ignoreNull = true)
    {
        $table = preg_replace('/[^a-zA-Z_]*/', '', $table);
        $where = '';
        if ($ignoreNull) {
            $where = " WHERE $column IS NOT NULL";
        }
        $sql = <<<SQL
SELECT
    $column, COUNT(*) AS Count, GROUP_CONCAT(ID) AS IDs
FROM
    $table
$where
GROUP BY
    $column
HAVING
    COUNT(*) > 1
SQL;
        return iterator_to_array(DB::query($sql));
    }

    public static function update($table, $data, $id)
    {
        $query = new SQLUpdate($table);
        reset($data);
        foreach ($data as $k => $v) {
            $query->assign($k, $v);
        }
        $query->addWhere(['ID' => $id]);
        return $query->execute();
    }

    public static function insert($table, $data)
    {
        $query = new SQLInsert($table);
        reset($data);
        foreach ($data as $k => $v) {
            $query->assign($k, $v);
        }
        return $query->execute();
    }

    public static function fastCount($table, $where = [])
    {
        $table = preg_replace('/[^a-zA-Z_]*/', '', $table);
        $sql = "SELECT COUNT(ID) FROM $table";
        $params = [];
        $keys = [];
        if (!empty($where)) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    $keys[] = "$k IN (" . implode(",", $v) . ")";
                } else {
                    $keys[] = "$k = ?";
                    $params[] = $v;
                }
            }
            $sql .= " WHERE " . implode(" AND ", $keys);
        }
        return (int) DB::prepared_query($sql, $params)->value();
    }
}
