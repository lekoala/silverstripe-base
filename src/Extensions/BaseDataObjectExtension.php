<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use Psr\Log\LoggerInterface;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Connect\Query;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\ORM\UnsavedRelationList;
use LeKoala\Base\Forms\BuildableFieldList;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * Improve DataObjects
 *
 * - cascade_delete relations should not be a relation, but a record editor
 * - summary fields should include subsite extra fields
 * - after delete, cleanup tables
 * - non versioned class should publish their own assets
 * - declarative cms fields : removed_fields, ...
 *
 * @property \LeKoala\Base\Blocks\Block|\LeKoala\Base\News\NewsCategory|\LeKoala\Base\News\NewsItem|\LeKoala\Base\Security\MemberAudit|\LeKoala\Base\Tags\Tag|\TimelineItem|\PortfolioCategory|\PortfolioItem|\SilverStripe\Assets\File|\SilverStripe\SiteConfig\SiteConfig|\SilverStripe\Versioned\ChangeSetItem|\SilverStripe\CMS\Model\SiteTree|\SilverStripe\Security\Group|\SilverStripe\Security\Member|\LeKoala\Base\Extensions\BaseDataObjectExtension $owner
 */
class BaseDataObjectExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields = BuildableFieldList::fromFieldList($fields);
        $cascade_delete = $this->owner->config()->cascade_deletes;
        // Anything that is deleted in cascade should not be a relation (most of the time!)
        $this->turnRelationsIntoRecordEditor($fields, $cascade_delete);

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
            'LinkTracking', 'FileTracking'
        ]);
    }

    /**
     * Quickly apply update to the model without using the ORM or changed LastEdited fields
     *
     * @param array $data
     * @return Query
     */
    public function directUpdate($data)
    {
        $schema = DataObjectSchema::create();
        $table = $schema->tableName(get_class($this->owner));
        $query = new SQLUpdate($table);
        foreach ($data as $k => $v) {
            $query->assign($k, $v);
        }
        $query->addWhere('ID = ' . $this->owner->ID);
        return $query->execute();
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
        /* @var $list ManyManyList */
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
     * @param string $class
     * @return bool
     */
    protected function isAssetClass($class)
    {
        return $class === Image::class || $class === File::class;
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
        if ($this->isVersioned()) {
            return;
        }

        $owns = $this->owner->config()->owns;
        foreach ($owns as $componentName => $componentClass) {
            if ($this->isAssetClass($componentClass)) {
                $component = $this->owner->getComponent($componentName);
                if ($component->isInDB() && !$component->isPublished()) {
                    $component->publishSingle();
                }
            }
        }
    }

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
                $GridFieldDataColumns = $config->getComponentByType(GridFieldDataColumns::class);
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

            $GridFieldDataColumns = $config->getComponentByType(GridFieldDataColumns::class);
            $display = $GridFieldDataColumns->getDisplayFields($gridfield);
            foreach ($data as $k => $v) {
                $display[$k] = $k;
            }
            $GridFieldDataColumns->setDisplayFields($display);
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
     * Call delete according to Versioned
     *
     * @return void
     */
    public function deleteAll()
    {
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
