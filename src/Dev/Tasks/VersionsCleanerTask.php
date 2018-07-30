<?php
namespace LeKoala\Base\Dev\Tasks;

use SilverStripe\ORM\DB;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Versioned\Versioned;

/**
 */
class VersionsCleanerTask extends BuildTask
{
    protected $description = 'Clean old versions.';
    private static $segment = 'VersionsCleanerTask';

    public function init()
    {
        $this->addOption("check_relations", "Inspect relations as well", true);
        $this->addOption("go", "Tick this to remove the records", false);
        $options = $this->askOptions();

        $go = $options['go'];
        $check_relations = $options['check_relations'];

        $versionedClasses = ClassHelper::extendedBy(Versioned::class);

        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $tableList = $schema->tableList();

        foreach ($versionedClasses as $class) {
            /* @var $singl SilverStripe\ORM\DataObject */
            $singl = $class::singleton();
            $table = $dataObjectSchema->tableName($class);
            $shortClass = ClassHelper::getClassWithoutNamespace($class);
            $liveTable = $table . '_Live';
            $versionedTable = $table . '_Versions';

            // It does not exist in the list, no need to worry about
            if (!isset($tableList[strtolower($versionedTable)])) {
                continue;
            }

            $selectSql = <<<SQL
SELECT ID FROM $versionedTable WHERE RecordID NOT IN (SELECT ID FROM $table)
SQL;
            $oldRecords = iterator_to_array(DB::query($selectSql));
            $count = count($oldRecords);
            $this->message("$count old records in $versionedTable");

            if ($go && $count) {
                $deleteSql = <<<SQL
DELETE FROM $versionedTable WHERE RecordID NOT IN (SELECT ID FROM $table)
SQL;
                DB::query($deleteSql);
                $this->message("Deleted the records", "deleted");
            }

            // Find related classes
            if (!$check_relations) {
                continue;
            }

            $relatedClasses = ClassHelper::relatedManyClasses($class);
            foreach ($relatedClasses as $relatedClass => $relations) {
                $relatedTable = $dataObjectSchema->tableName($relatedClass);

                foreach ($relations as $relationName) {
                    $relatedRelationTable = $relatedTable . '_' . $relationName;
                    if (!isset($tableList[strtolower($relatedRelationTable)])) {
                        continue;
                    }

                    $foreignName = $shortClass . 'ID';

                    $relatedSelectSql = <<<SQL
SELECT ID FROM $relatedRelationTable WHERE $foreignName NOT IN (SELECT ID FROM $table)
SQL;
                    $oldRelatedRecords = iterator_to_array(DB::query($relatedSelectSql));
                    $relatedCount = count($oldRelatedRecords);
                    $this->message("$relatedCount old records in $relatedRelationTable");
                    if ($go && $relatedCount) {
                        $deleteRelatedSql = <<<SQL
DELETE FROM $relatedRelationTable WHERE $foreignName NOT IN (SELECT ID FROM $table)
SQL;
                        DB::query($deleteRelatedSql);
                        $this->message("Deleted the related records", "deleted");
                    }
                }
            }
        }
    }
}
