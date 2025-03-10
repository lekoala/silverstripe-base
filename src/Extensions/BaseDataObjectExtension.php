<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use Psr\Log\LoggerInterface;
use SilverStripe\Assets\File;
use GridFieldSoftDeleteAction;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Connect\Query;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\ORM\UnsavedRelationList;
use LeKoala\Base\Forms\BuildableFieldList;
use SilverStripe\Assets\Shortcodes\FileLink;
use LeKoala\Base\Forms\GridField\GridFieldHelper;
use LeKoala\Base\Helpers\DatabaseHelper;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Assets\Shortcodes\FileLinkTracking;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormScaffolder;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;

/**
 * Improve DataObjects
 *
 * - cascade_delete relations should not be a relation, but a record editor
 * - summary fields should include subsite extra fields
 * - after delete, cleanup tables
 * - non versioned class should publish their own assets
 * - declarative cms fields : removed_fields, ...
 *
 * @property \SilverStripe\ORM\DataObject|\LeKoala\Base\Extensions\BaseDataObjectExtension $owner
 */
class BaseDataObjectExtension extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields = BuildableFieldList::fromFieldList($fields);
        $cascade_delete = $this->owner->config()->cascade_deletes;
        // Anything that is deleted in cascade should not be a relation (most of the time!)
        $this->turnRelationsIntoRecordEditor($fields, $cascade_delete);

        $optimize = !$this->owner->config()->dont_remove_gridfield_search;
        if ($optimize) {
            $relations = array_keys(array_merge(
                $this->owner->config()->has_many,
                $this->owner->config()->many_many,
            ));
            $this->removeSearchForms($fields, $relations);
        }

        $many_many = $this->owner->config()->many_many;
        $this->improveAssetsGridField($fields, $many_many);

        // extraFields are wanted!
        $many_many_extraFields = $this->owner->config()->many_many_extraFields;
        $this->expandGridFieldSummary($fields, $many_many_extraFields);

        // removed fields
        $removed_fields = $this->owner->config()->removed_fields;
        $this->removeFields($fields, $removed_fields);

        // readonly fields
        $readonly_fields = $this->owner->config()->readonly_fields;
        if ($readonly_fields) {
            foreach ($readonly_fields as $readonly) {
                $fields->makeFieldReadonly($readonly);
            }
        }

        // remove tracking tabs
        $fields->removeByName([
            'LinkTracking',
            'FileTracking'
        ]);
    }

    /**
     * Helper to write without version even for non versioned objects
     *
     * @return int The ID of the record
     */
    public function writeWithoutVersionIfPossible()
    {
        if ($this->owner->hasExtension(Versioned::class)) {
            $this->owner->writeWithoutVersion();
        }
        return $this->owner->write();
    }

    /**
     * Quickly apply update to the model without using the ORM or changed LastEdited fields
     * Don't mix multiple target table for your data. This first field will
     * determine which table is used
     *
     * @param array $data
     * @return Query|null
     */
    public function directUpdate($data)
    {
        $id = $this->owner->ID;
        if (!$id) {
            return null;
        }
        $schema = DataObjectSchema::singleton();
        $table = $schema->tableForField(get_class($this->owner), key($data));
        return DatabaseHelper::update($table, $data, $id);
    }

    /**
     * @deprecated
     * @param FormScaffolder $fs
     * @return void
     */
    public function updateFormScaffolder(FormScaffolder $fs)
    {
        $ignored_fields = $this->owner->config()->ignored_fields;
        if (!empty($ignored_fields) && property_exists($fs, 'ignoreFields')) {
            //@phpstan-ignore-next-line
            $fs->ignoreFields = $ignored_fields;
        }
    }

    public function isBeingEdited(): bool
    {
        if (!Controller::has_curr()) {
            return false;
        }
        /** @var LeftAndMain $ctrl  */
        $ctrl = Controller::curr();
        if (!$ctrl instanceof LeftAndMain) {
            return false;
        }
        if ($ctrl instanceof ModelAdmin) {
            // This is the base class being edited, it does not take into account the EditForm
            // if ($ctrl->getModelClass() != get_class($this->owner)) {
            //     return false;
            // }
            // This is a bit of a hack
            $ID = $this->owner->ID;

            $url = $ctrl->getRequest()->getURL();
            if ($ctrl->getRequest()->isPOST()) {
                // Current url is alway /ItemEditForm because we are posting
                $url = $ctrl->getBackURL();
            }
            return strpos($url, "/item/$ID/edit") !== false;
        }

        return $this->owner->ID == $ctrl->currentPageID();
    }

    /**
     * Syntax helper for getExtraData
     *
     * @param string $relation
     * @param int $id
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getManyManyExtraData($relation, $id, $key, $default = null)
    {
        /** @var \SilverStripe\ORM\ManyManyList $list */
        $list = $this->owner->$relation();
        if ($list instanceof UnsavedRelationList) {
            return $default;
        }
        $val = $list->getExtraData($relation, $id);
        if (!$val) {
            return $default;
        }
        return $val[$key];
    }

    public function augmentDatabase()
    {
        $db = $this->owner->uninherited('db');
        $class = get_class($this->owner);
        // TODO: rename this see https://github.com/silverstripe/silverstripe-framework/issues/8088
        if (isset($db['Data'])) {
            Injector::inst()->get(LoggerInterface::class)->debug("Class $class should not have a Data field");
        }
        // This is a reserved keyword as well
        $has_one = $this->owner->uninherited('has_one');
        if (isset($has_one['Record'])) {
            Injector::inst()->get(LoggerInterface::class)->debug("Class $class should not have a Record relation");
        }
    }

    /**
     * @return bool
     */
    protected function isVersioned()
    {
        return $this->owner->hasExtension(Versioned::class);
    }

    /**
     * Class names to consider for our getFiles* functions
     * @return array
     */
    public function listFileTypes()
    {
        return [
            Image::class,
            File::class,
        ];
    }

    /**
     * Get all file relations
     *
     * @return array with three keys : has_one, has_many, many_many
     */
    public function getAllFileRelations()
    {
        return [
            'has_one' => $this->getHasOneFileRelations(),
            'has_many' => $this->getHasManyFileRelations(),
            'many_many' => $this->getManyManyFileRelations(),
        ];
    }
    /**
     * Files in hasOne
     *
     * @return array
     */
    public function getHasOneFileRelations()
    {
        return $this->findFileRelations($this->owner->hasOne());
    }

    /**
     * Files in hasMany
     *
     * @return array
     */
    public function getHasManyFileRelations()
    {
        return $this->findFileRelations($this->owner->hasMany());
    }

    /**
     * Files in manyMany
     *
     * @return array
     */
    public function getManyManyFileRelations()
    {
        return $this->findFileRelations($this->owner->manyMany());
    }

    /**
     * Find file relations in a relation list
     *
     * @param array $arr list of relations
     * @return array An array like [Relation, Relation2, ...]
     */
    public function findFileRelations($arr)
    {
        if (!$arr) {
            return [];
        }
        $fileTypes = $this->listFileTypes();
        $res = [];
        foreach ($arr as $name => $type) {
            if (in_array($type, $fileTypes)) {
                $res[] = $name;
            }
        }
        return $res;
    }

    /**
     * @param string $class
     * @return bool
     */
    protected function isAssetClass($class)
    {
        return $class === Image::class || $class === File::class;
    }

    public function onBeforeWrite()
    {
        $this->assignAssets();
    }

    public function onBeforeDelete()
    {
        $this->cleanupAssets();
    }

    public function onAfterDelete()
    {
        $this->cleanupManyManyTables();
    }

    public function onAfterWrite()
    {
        $this->publishOwnAssets();
    }

    protected function publishOwnAssets()
    {
        // Let Versioned class do its things
        if ($this->isVersioned()) {
            return;
        }

        // Publish owned files automatically
        $owns = $this->owner->config()->owns;
        foreach ($owns as $componentName => $componentClass) {
            if ($this->isAssetClass($componentClass)) {
                $component = $this->owner->getComponent($componentName);
                if ($component->isInDB() && !$component->isPublished()) {
                    $component->publishSingle();
                }
            }
        }

        // Cleanup assets
        $relations = $this->owner->getAllFileRelations();
        $changedFields = $this->owner->getChangedFields(true);
        foreach ($relations as $type => $names) {
            foreach ($names as $name) {
                if ($type == 'has_one') {
                    $field = $name . 'ID';
                    // Check if we need to delete previous file
                    if (isset($changedFields[$field])) {
                        $before = $changedFields[$field]['before'];
                        $after = $changedFields[$field]['after'];
                        // Clean old file
                        if ($before != $after) {
                            $oldFile = File::get()->byID($before);
                            if ($oldFile && $oldFile->ID) {
                                $oldFile->deleteAll();
                            }
                        }
                    }
                } else {
                    //  How to handle has_many and many_many because they are not visible in changedFields ?
                }
            }
        }
    }

    /**
     * @param FieldList $fields
     * @param array $arr
     * @return void
     */
    public function removeFields(FieldList $fields, $arr)
    {
        if (!$arr) {
            return;
        }
        foreach ($arr as $name) {
            $fields->removeByName($name);
        }
    }

    /**
     * @param BuildableFieldList $fields
     * @param array $arr
     * @return void
     */
    protected function improveAssetsGridField(BuildableFieldList $fields, $arr)
    {
        if (!$arr) {
            return;
        }
        foreach ($arr as $relation => $class) {
            if (!$this->isAssetClass($class)) {
                continue;
            }

            if ($class == Image::class) {
                $gridfield = $fields->getGridField($relation);
                if (!$gridfield) {
                    continue;
                }
                $config = $gridfield->getConfig();
                $gridfield->addExtraClass('gridfield-gallery');
                $GridFieldDataColumns = GridFieldHelper::getGridFieldDataColumns($config);
                $display = [
                    'Name' => 'Name',
                    'LargeAssetThumbnail' => 'Thumbnail'
                ];
                $GridFieldDataColumns->setDisplayFields($display);
            }
        }
    }

    /**
     * @param BuildableFieldList $fields
     * @param array $arr
     * @return void
     */
    protected function expandGridFieldSummary(BuildableFieldList $fields, $arr)
    {
        if (!$arr) {
            return;
        }
        foreach ($arr as $relation => $data) {
            $gridfield = $fields->getGridField($relation);
            if (!$gridfield) {
                continue;
            }
            $config = $gridfield->getConfig();

            $GridFieldDataColumns = GridFieldHelper::getGridFieldDataColumns($config);
            if ($GridFieldDataColumns) {
                $display = $GridFieldDataColumns->getDisplayFields($gridfield);
                foreach ($data as $k => $v) {
                    $display[$k] = $k;
                }
                $GridFieldDataColumns->setDisplayFields($display);
            }
        }
    }

    /**
     * @param BuildableFieldList $fields
     * @param array $arr List of relations
     * @return void
     */
    protected function removeSearchForms(BuildableFieldList $fields, $arr)
    {
        if (!$arr) {
            return;
        }
        foreach ($arr as $relation) {
            $gridfield = $fields->getGridField($relation);
            if (!$gridfield) {
                continue;
            }
            $config = $gridfield->getConfig();

            // Optimize search form (if request on the dataobject)
            if (singleton($gridfield->getModelClass())->config()->disable_search_form) {
                $config->removeComponentsByType(GridFieldFilterHeader::class);
            }
        }
    }

    /**
     * @param BuildableFieldList $fields
     * @param array $arr List of relations
     * @return void
     */
    protected function turnRelationsIntoRecordEditor(BuildableFieldList $fields, $arr)
    {
        if (!$arr) {
            return;
        }
        $ownerClass = get_class($this->owner);
        $allSubclasses = ClassInfo::ancestry($ownerClass, true);

        foreach ($arr as $relation) {
            $relationClass = $this->owner->getRelationClass($relation);
            $gridfield = $fields->getGridField($relation);
            if (!$gridfield) {
                continue;
            }
            $config = $gridfield->getConfig();
            $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);

            $deleteAction = $config->getComponentByType(GridFieldDeleteAction::class);
            if ($deleteAction) {
                $config->removeComponentsByType(GridFieldDeleteAction::class);
                if ($this->owner->hasExtension(\SoftDeletable::class)) {
                    $config->addComponent(new GridFieldSoftDeleteAction());
                } else {
                    $config->addComponent(new GridFieldDeleteAction());
                }
            }

            $dataColumns = GridFieldHelper::getGridFieldDataColumns($config);
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
     * Call delete according to Versioned
     *
     * @return void
     */
    public function deleteAll()
    {
        // Cannot delete without ID
        if (!$this->owner->ID) {
            return;
        }
        if ($this->isVersioned()) {
            $this->owner->deleteFromStage(Versioned::LIVE);
            $this->owner->deleteFromStage(Versioned::DRAFT);
        } else {
            $this->owner->delete();
        }
    }

    /**
     * Write to all stages
     *
     * @return void
     */
    public function writeAll()
    {
        if ($this->isVersioned()) {
            $this->owner->write();
            $this->owner->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        } else {
            $this->owner->write();
        }
    }

    protected function assignAssets()
    {
        $rel = $this->getAllFileRelations();
        $owns =  $this->owner->config()->owns;

        // No ownership!
        if (!$owns) {
            return;
        }

        foreach ($rel as $relType => $list) {
            switch ($relType) {
                case 'has_one':
                    foreach ($list as $name) {
                        // owns match has_one name (without id)
                        // if not owned, don't assign
                        if (!in_array($name, $owns)) {
                            continue;
                        }
                        $field = $name . "ID";
                        // no file, skip
                        if (!$this->owner->$field) {
                            continue;
                        }
                        // not changed, skip
                        if (!$this->owner->isChanged($field)) {
                            continue;
                        }
                        /** @var File|BaseDataObjectExtension $rec */
                        $rec = $this->owner->$name();
                        // only write if necessary
                        if ($rec && $rec->ID && $rec->ObjectID != $this->owner->ID) {
                            $rec->ObjectID = $this->owner->ID;
                            $rec->ObjectClass = get_class($this->owner);
                            if ($rec->isChanged()) {
                                $rec->writeWithoutVersionIfPossible();
                            }
                        }
                    }
                    break;
                case 'has_many':
                case 'many_many':
                    foreach ($list as $name) {
                        if (!in_array($name, $owns)) {
                            continue;
                        }
                        // foreach ($this->owner->$name() as $rec) {
                        //     if ($rec && $rec->ID && $rec->ObjectID != $this->owner->ID) {
                        //         $rec->ObjectID = $this->owner->ID;
                        //         $rec->ObjectClass = get_class($this->owner);
                        //         $rec->write();
                        //     }
                        // }
                    }
                    break;
            }
        }
    }

    /**
     * When you delete a record
     *
     * @return void
     */
    protected function cleanupAssets()
    {
        // We should not cleanup tables on versioned items because they can be restored
        if ($this->isVersioned()) {
            return;
        }

        // It's a file tracking, don't cleanup!
        if (in_array(get_class($this->owner), [FileLink::class, FileLinkTracking::class])) {
            return;
        }

        // If we have a cascade delete, no need to worry
        if (!empty($this->owner->config()->cascade_deletes)) {
            return;
        }

        $rel = $this->getAllFileRelations();
        $owns =  $this->owner->config()->owns;

        if (!$owns) {
            return;
        }

        foreach ($rel as $relType => $list) {
            switch ($relType) {
                case 'has_one':
                    foreach ($list as $name) {
                        if (!in_array($name, $owns)) {
                            continue;
                        }
                        $field = $name . "ID";
                        // no file, skip
                        if (!$this->owner->$field) {
                            continue;
                        }
                        $rec = $this->owner->$name();
                        if ($rec && $rec->ID) {
                            $rec->deleteAll();
                        }
                    }
                    break;
                case 'has_many':
                case 'many_many':
                    foreach ($list as $name) {
                        if (!in_array($name, $owns)) {
                            continue;
                        }
                        foreach ($this->owner->$name() as $rec) {
                            if ($rec && $rec->ID) {
                                $rec->deleteAll();
                            }
                        }
                    }
                    break;
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
        // We should not cleanup tables on versioned items because they can be restored
        if ($this->isVersioned()) {
            return;
        }
        $many_many = $this->owner->manyMany();
        foreach ($many_many as $relation => $type) {
            $manyManyComponents = $this->owner->getManyManyComponents($relation);
            // Cannot cleanup many many through
            if ($manyManyComponents instanceof ManyManyThroughList) {
                continue;
            }
            $table = $manyManyComponents->getJoinTable();
            $key = $manyManyComponents->getForeignKey();
            $id = $this->owner->ID;
            $sql = "DELETE FROM $table WHERE $key = $id";
            DB::query($sql);
        }
    }
}
