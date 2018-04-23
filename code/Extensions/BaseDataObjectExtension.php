<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\Versioned;
use LeKoala\Base\Forms\BuildableFieldList;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * Improve DataObjects
 */
class BaseDataObjectExtension extends DataExtension
{

    public function updateCMSFields(FieldList $fields)
    {
        $fields = BuildableFieldList::fromFieldList($fields);
        $cascade_delete = $this->owner->config()->cascade_deletes;
        // Anything that is deleted in cascade should not be a relation
        foreach ($cascade_delete as $class) {
            $gridfield = $fields->getGridField($class);
            $config = $gridfield->getConfig();
            $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
            $config->removeComponentsByType(GridFieldDeleteAction::class);
            $config->addComponent(new GridFieldDeleteAction());
        }
    }

    public function onAfterDelete()
    {
        // Clean up any many_many mapping table after delete for non versioned objects
        if (!$this->owner->hasExtension(Versioned::class)) {
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
}
