<?php

namespace LeKoala\Base\Helpers;

use Exception;
use SqlFormatter;
use RuntimeException;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\SQLite\SQLite3Database;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\UnsavedRelationList;
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
     * Avoid the infamous Cannot filter "Table"."ID" against an empty set
     *
     * @param array|string $IDs
     * @return array|string The list or 0
     */
    public static function getValidIDs($IDs)
    {
        if (empty($IDs)) {
            $IDs = 0;
        }
        return $IDs;
    }

    public static function preventRemoval(DataObject &$obj, $fields = [], $allFiles = false)
    {
        if ($allFiles && $obj->hasMethod('getAllFileRelations')) {
            $filesRelations = $obj->getAllFileRelations();
            foreach ($filesRelations as $relType => $rels) {
                foreach ($rels as $rel) {
                    $fields[] = $rel . "ID";
                }
            }
        }

        $changedFields = $obj->getChangedFields(true, DataObject::CHANGE_VALUE);
        foreach ($changedFields as $changedField => $changes) {
            // Revert value if we prevent removal
            if (in_array($changedField, $fields) && empty($changes['after'])) {
                $obj->$changedField = $changes['before'];
            }
        }
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
        if (is_array($values) && empty($values)) {
            return;
        }
        if (is_string($values) || is_numeric($values)) {
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

    public static function appendInClause($str, $values, $field = "ID")
    {
        if (empty($values)) {
            return $str;
        }

        return $str . " $field IN (" . implode(",", $values) . ")";
    }

    /**
     * Join two where clauses either with AND or OR
     *
     * @param array $baseWhere
     * @param array $newWhere
     * @param string $type
     * @return array
     */
    public static function joinWhere($baseWhere, $newWhere, $type = "OR")
    {
        $w = [];
        $sqlParts = [];
        $sqlParams = [];
        foreach ($baseWhere as $sql => $params) {
            $sqlParts[] = $sql;
            if (!is_array($params)) {
                $params = [$params];
            }
            $sqlParams = array_merge($sqlParams, $params);
        }
        $w[implode("$type", $sqlParts)] = $sqlParams;
        $sqlParts = [];
        $sqlParams = [];
        foreach ($newWhere as $sql => $params) {
            $sqlParts[] = $sql;
            if (!is_array($params)) {
                $params = [$params];
            }
            $sqlParams = array_merge($sqlParams, $params);
        }
        $w[implode("$type", $sqlParts)] = $sqlParams;
        return $w;
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
        // COUNT(*) is the fastest
        $sql = "SELECT COUNT(*) FROM $table";
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

    /**
     * Inject some fields from a has_one relation into the DataObject extra fields
     * Fields are made available under $ComponentName_$FieldName
     *
     * @param DataList $list
     * @param string $componentName
     * @param array $fields
     * @return DataList|ManyManyList
     */
    public static function inject($list, $componentName, $fields = [])
    {
        if (empty($fields)) {
            return $list;
        }

        $localClass = $list->dataClass();
        if (!$localClass) {
            throw new RuntimeException("No class defined for this DataList");
        }

        $schema = DataObject::getSchema();
        $foreignClass = $schema->hasOneComponent($localClass, $componentName);
        if (!$foreignClass) {
            throw new RuntimeException("No has one component");
        }

        // Simply ignore since the list is not built yet
        if ($list instanceof UnsavedRelationList) {
            return $list;
        }

        // Build join expression
        $foreignPrefix = $componentName;
        $localPrefix = null;

        $table = $schema->tableName($foreignClass);
        $tableAlias = $componentName . $table;

        $localKey = $componentName . "ID";
        $localIDColumn = $schema->sqlColumnForField($localClass, $localKey, $localPrefix);
        $foreignKey = "ID";
        $foreignKeyIDColumn = $schema->sqlColumnForField($foreignClass, $foreignKey, $foreignPrefix);
        $joinExpression = "{$foreignKeyIDColumn} = {$localIDColumn}";

        // Create a list of aliased fields for selectField()
        $aliasedFields = [];
        foreach ($fields as $f) {
            $alias = $componentName . '_' . $f;
            $col = $schema->sqlColumnForField($foreignClass, $f, $foreignPrefix);
            $aliasedFields[$col] = $alias;
        }

        // Apply join and alter DataQuery in order to query our extra fields
        $list = $list->leftJoin($table, $joinExpression, $tableAlias);
        $list = $list->alterDataQuery(function (DataQuery $dataQuery) use ($aliasedFields) {
            foreach ($aliasedFields as $col => $alias) {
                $dataQuery->selectField($col, $alias);
            }
            return $dataQuery;
        });
        return $list;
    }

    /**
     * Turns out columnUnique doesn't work as expected
     * @link https://github.com/silverstripe/silverstripe-framework/issues/10452
     * @param DataList $list
     * @param string $colName
     * @return array
     */
    public static function uniqueCol(DataList $list, $colName)
    {
        return array_unique($list->columnUnique($colName));
    }
}
