<?php
namespace LeKoala\Base\Dev;

use SilverStripe\ORM\DB;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;

/**
 * SilverStripe never delete your tables or fields. Be careful if your database has other tables than SilverStripe!
 *
 * @author lekoala
 */
class DropUnusedDatabaseObjectsTask extends BuildTask
{

    protected $title = "Drop Unused Database Objects";
    protected $description = 'Drop unused tables and fields from your db by comparing current database tables with your dataobjects.';
    private static $segment = 'DropUnusedDatabaseObjectsTask';

    public function run($request)
    {
        parent::run($request);

        $this->addOption("tables", "Clean unused tables", true);
        $this->addOption("fields", "Clean unused fields", true);
        $this->addOption("go", "Set this to 1 to proceed", 0);

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
        $this->removeTables($request);
        $this->removeFields($request);
    }

    protected function removeFields($request)
    {
        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $classes = $this->getClassesWithTables();
        $tableList = $schema->tableList();

        $go = $request->getVar('go');

        $this->message('<h2>Fields</h2>');

        $empty = true;

        foreach ($classes as $class) {
            $table = $dataObjectSchema->tableName($class);
            $lcTable = strtolower($table);

            // It does not exist in the list, no need to worry about
            if (!isset($tableList[$lcTable])) {
                continue;
            }
            $toDrop = [];

            $fields = $dataObjectSchema->databaseFields($class);
            $list = $schema->fieldList($lcTable);

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

            if (empty($toDrop)) {
                continue;
            }

            $empty = false;
            if ($go) {
                $this->dropColumns($table, $toDrop);
                $this->message("Dropped " . implode(',', $toDrop) . " for $table", "obsolete");
            } else {
                $this->message("Would drop " . implode(',', $toDrop) . " for $table", "obsolete");
            }
        }

        if ($empty) {
            $this->message("No fields to remove", "repaired");
        }
    }

    protected function removeTables($request)
    {
        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $classes = $this->getClassesWithTables();
        $tableList = $schema->tableList();
        $tablesToRemove = $tableList;

        $go = $request->getVar('go');

        $this->message('<h2>Tables</h2>');

        foreach ($classes as $class) {
            $table = $dataObjectSchema->tableName($class);
            $lcTable = strtolower($table);

            // It does not exist in the list, keep to remove later
            if (!isset($tableList[$lcTable])) {
                continue;
            }

            // Remove from the list
            self::removeFromArray($lcTable, $tablesToRemove);
            self::removeFromArray($lcTable . '_live', $tablesToRemove);
            self::removeFromArray($lcTable . '_versions', $tablesToRemove);

            // Relations
            $hasMany = $class::config()->has_many;
            if (!empty($hasMany)) {
                foreach ($hasMany as $rel => $obj) {
                    self::removeFromArray($lcTable . '_' . strtolower($rel), $tablesToRemove);
                }
            }
            $manyMany = $class::config()->many_many;
            if (!empty($manyMany)) {
                foreach ($manyMany as $rel => $obj) {
                    self::removeFromArray($lcTable . '_' . strtolower($rel), $tablesToRemove);
                }
            }
        }

        //at this point, we should only have orphans table in dbTables var
        foreach ($tablesToRemove as $lcTable => $table) {
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
