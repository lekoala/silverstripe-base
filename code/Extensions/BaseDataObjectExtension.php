<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\Versioned;
use LeKoala\Base\Forms\BuildableFieldList;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use SilverStripe\Core\ClassInfo;

/**
 * Improve DataObjects
 */
class BaseDataObjectExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields = BuildableFieldList::fromFieldList($fields);
        $cascade_delete = $this->owner->config()->cascade_deletes;
        // Anything that is deleted in cascade should not be a relation (most of the time!)
        $this->turnRelationsIntoRecordEditor($cascade_delete, $fields);

        // extraFields are wanted!
        $extraFields = $this->owner->config()->many_many_extraFields;
        $this->expandGridFieldSummary($extraFields, $fields);
    }

    public function onAfterDelete()
    {
        if ($this->owner->hasExtension(Versioned::class)) {
            return;
        }
        $this->cleanupManyManyTables();
    }

    protected function expandGridFieldSummary($arr, BuildableFieldList $fields)
    {
        if (!$arr) {
            return;
        }
        foreach ($arr as $class => $data) {
            $gridfield = $fields->getGridField($class);
            if (!$gridfield) {
                continue;
            }
            $config = $gridfield->getConfig();

            $GridFieldDataColumns = $config->getComponentByType(GridFieldDataColumns::class);
            $display = $GridFieldDataColumns->getDisplayFields($gridfield);
            foreach ($data as $k => $v) {
                $display[$k] = $k;
            }
            $GridFieldDataColumns->setDisplayFields($display);
        }
    }

    /**
     * @param array $arr List of relations
     * @param BuildableFieldList $fields
     * @return void
     */
    protected function turnRelationsIntoRecordEditor($arr, BuildableFieldList $fields)
    {
        if (!$arr) {
            return;
        }
        $ownerClass = get_class($this->owner);
        $allSubclasses = ClassInfo::ancestry($ownerClass, true);

        foreach ($arr as $class) {
            $sanitisedClass = str_replace('\\', '-', $class);
            $gridfield = $fields->getGridField($sanitisedClass);
            if (!$gridfield) {
                continue;
            }
            $config = $gridfield->getConfig();
            $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);

            $deleteAction = $config->getComponentByType(GridFieldDeleteAction::class);
            if ($deleteAction) {
                $config->removeComponentsByType(GridFieldDeleteAction::class);
                $config->addComponent(new GridFieldDeleteAction());
            }

            $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);
            if ($dataColumns) {
                $displayFields = $dataColumns->getDisplayFields($gridfield);
                $newDisplayFields = [];
                // Strip any columns referencing current or parent class
                foreach ($displayFields as $k => $v) {
                    foreach ($allSubclasses as $lcClass => $class) {
                        if (strpos($k, $class . '.') === 0) {
                            continue 2;
                        }
                    }
                    $newDisplayFields[$k] = $v;
                }
                $dataColumns->setDisplayFields($newDisplayFields);
            }
        }
    }

    /**
     * SilverStripe does not delete by default records in many_many table
     * leaving many orphans rows
     *
     * Run this to avoid the problem
     *
     * @return void
     */
    protected function cleanupManyManyTables()
    {
        $many_many = $this->owner->manyMany();
        foreach ($many_many as $relation => $type) {
            $manyManyComponents = $this->owner->getManyManyComponents($relation);
            $table = $manyManyComponents->getJoinTable();
            $key = $manyManyComponents->getForeignKey();
            $id = $this->owner->ID;
            $sql = "DELETE FROM $table WHERE $key = $id";
            DB::query($sql);
        }
    }
}
