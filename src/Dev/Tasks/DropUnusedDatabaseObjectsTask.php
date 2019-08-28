<?php

namespace LeKoala\Base\Dev\Tasks;

use SilverStripe\ORM\DB;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Versioned\Versioned;

/**
 * SilverStripe never delete your tables or fields. Be careful if your database has other tables than SilverStripe!
 *
 * @author lekoala
 */
class DropUnusedDatabaseObjectsTask extends BuildTask
{
    protected $description = 'Drop unused tables and fields from your db by comparing current database tables with your dataobjects.';
    private static $segment = 'DropUnusedDatabaseObjectsTask';

    public function init()
    {
        $request = $this->getRequest();

        $this->addOption("tables", "Clean unused tables", true);
        $this->addOption("fields", "Clean unused fields", true);
        $this->addOption("go", "Tick this to proceed", false);

        $options = $this->askOptions();

        $tables = $options['tables'];
        $fields = $options['fields'];
        $go = $options['go'];

        if (!$go) {
            echo ('Previewing what this task is about to do.');
        } else {
            echo ("Let's clean this up!");
        }
        echo ('<hr/>');
        $this->removeTables($request, $go);
        $this->removeFields($request, $go);
    }

    protected function removeFields($request, $go = false)
    {
        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $classes = $this->getClassesWithTables();
        $tableList = $schema->tableList();

        $this->message('<h2>Fields</h2>');

        $empty = true;

        foreach ($classes as $class) {
            /* @var $singl SilverStripe\ORM\DataObject */
            $singl = $class::singleton();
            $baseClass = $singl->baseClass();
            $table = $dataObjectSchema->tableName($baseClass);
            $lcTable = strtolower($table);

            // It does not exist in the list, no need to worry about
            if (!isset($tableList[$lcTable])) {
                continue;
            }
            $toDrop = [];

            $fields = $dataObjectSchema->databaseFields($class);
            // We must pass the regular table name
            $list = $schema->fieldList($table);
            // We can compare DataObject schema with actual schema
            foreach ($list as $fieldName => $type) {
                /// Never drop ID
                if ($fieldName == 'ID') {
                    continue;
                }
                if (!isset($fields[$fieldName])) {
                    $toDrop[] = $fieldName;
                }
            }

            if (!empty($toDrop)) {
                $empty = false;
                if ($go) {
                    $this->dropColumns($table, $toDrop);
                    $this->message("Dropped " . implode(',', $toDrop) . " for $table", "obsolete");
                } else {
                    $this->message("Would drop " . implode(',', $toDrop) . " for $table", "obsolete");
                }
            }

            // Localised fields support
            if ($singl->hasExtension("\\TractorCow\\Fluent\\Extension\\FluentExtension")) {
                $toDrop = [];
                $localeTable = $table . '_Localised';
                $localeFields = $singl->getLocalisedFields($baseClass);
                $localeList = $schema->fieldList($localeTable);
                foreach ($localeList as $fieldName => $type) {
                    /// Never drop locale fields
                    if (in_array($fieldName, ['ID', 'RecordID', 'Locale'])) {
                        continue;
                    }
                    if (!isset($localeFields[$fieldName])) {
                        $toDrop[] = $fieldName;
                    }
                }
                if (!empty($toDrop)) {
                    $empty = false;
                    if ($go) {
                        $this->dropColumns($localeTable, $toDrop);
                        $this->message("Dropped " . implode(',', $toDrop) . " for $localeTable", "obsolete");
                    } else {
                        $this->message("Would drop " . implode(',', $toDrop) . " for $localeTable", "obsolete");
                    }
                }
            }
        }

        if ($empty) {
            $this->message("No fields to remove", "repaired");
        }
    }

    protected function removeTables($request, $go = false)
    {
        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $classes = $this->getClassesWithTables();
        $allDataObjects = array_values($this->getValidDataObjects());
        $tableList = $schema->tableList();
        $tablesToRemove = $tableList;

        $this->message('<h2>Tables</h2>');

        foreach ($classes as $class) {
            /* @var $singl SilverStripe\ORM\DataObject */
            $singl = $class::singleton();
            $table = $dataObjectSchema->tableName($class);
            $lcTable = strtolower($table);

            // It does not exist in the list, keep to remove later
            if (!isset($tableList[$lcTable])) {
                continue;
            }

            self::removeFromArray($lcTable, $tablesToRemove);
            // Remove from the list versioned tables
            if ($singl->hasExtension(Versioned::class)) {
                self::removeFromArray($lcTable . '_live', $tablesToRemove);
                self::removeFromArray($lcTable . '_versions', $tablesToRemove);
            }
            // Remove from the list fluent tables
            if ($singl->hasExtension("\\TractorCow\\Fluent\\Extension\\FluentExtension")) {
                self::removeFromArray($lcTable . '_localised', $tablesToRemove);
                self::removeFromArray($lcTable . '_localised_live', $tablesToRemove);
                self::removeFromArray($lcTable . '_localised_versions', $tablesToRemove);
            }

            // Relations
            $hasMany = $class::config()->has_many;
            if (!empty($hasMany)) {
                foreach ($hasMany as $rel => $obj) {
                    self::removeFromArray($lcTable . '_' . strtolower($rel), $tablesToRemove);
                }
            }
            // We catch relations without own classes later on
            $manyMany = $class::config()->many_many;
            if (!empty($manyMany)) {
                foreach ($manyMany as $rel => $obj) {
                    self::removeFromArray($lcTable . '_' . strtolower($rel), $tablesToRemove);
                }
            }
        }

        //at this point, we should only have orphans table in dbTables var
        foreach ($tablesToRemove as $lcTable => $table) {
            // Remove many_many tables without own base table
            if (strpos($table, '_') !== false) {
                $parts = explode('_', $table);
                $potentialClass = $parts[0];
                $potentialRelation = $parts[1];
                foreach ($allDataObjects as $dataObjectClass) {
                    $classParts = explode('\\', $dataObjectClass);
                    $tableClass = end($classParts);
                    if ($tableClass == $potentialClass) {
                        $manyManyRelations = $dataObjectClass::config()->many_many;
                        if (isset($manyManyRelations[$potentialRelation])) {
                            continue 2;
                        }
                    }
                }
            }
            if ($go) {
                DB::query('DROP TABLE `' . $table . '`');
                $this->message("Dropped $table", 'obsolete');
            } else {
                $this->message("Would drop $table", 'obsolete');
            }
        }

        if (empty($tablesToRemove)) {
            $this->message("No table to remove", "repaired");
        }
    }

    /**
     * All dataobjects
     *
     * @return array
     */
    protected function getValidDataObjects()
    {
        $list = ClassInfo::getValidSubClasses(DataObject::class);
        array_shift($list);
        return $list;
    }

    /**
     * @return array
     */
    protected function getClassesWithTables()
    {
        return ClassInfo::dataClassesFor(DataObject::class);
    }

    public static function removeFromArray($val, &$arr)
    {
        if (isset($arr[$val])) {
            unset($arr[$val]);
        }
    }

    public function dropColumns($table, $columns)
    {
        switch (get_class(DB::get_conn())) {
            case 'SQLite3Database':
                $this->sqlLiteDropColumns($table, $columns);
                break;
            default:
                $this->sqlDropColumns($table, $columns);
                break;
        }
    }

    public function sqlDropColumns($table, $columns)
    {
        DB::query("ALTER TABLE \"$table\" DROP \"" . implode('", DROP "', $columns) . "\"");
    }

    public function sqlLiteDropColumns($table, $columns)
    {
        $newColsSpec = $newCols = [];
        foreach (DB::get_conn()->fieldList($table) as $name => $spec) {
            if (in_array($name, $columns)) {
                continue;
            }
            $newColsSpec[] = "\"$name\" $spec";
            $newCols[] = "\"$name\"";
        }

        $queries = [
            "BEGIN TRANSACTION",
            "CREATE TABLE \"{$table}_cleanup\" (" . implode(',', $newColsSpec) . ")",
            "INSERT INTO \"{$table}_cleanup\" SELECT " . implode(',', $newCols) . " FROM \"$table\"",
            "DROP TABLE \"$table\"",
            "ALTER TABLE \"{$table}_cleanup\" RENAME TO \"{$table}\"",
            "COMMIT"
        ];

        foreach ($queries as $query) {
            DB::query($query . ';');
        }
    }
}
