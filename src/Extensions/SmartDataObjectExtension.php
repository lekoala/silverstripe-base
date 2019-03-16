<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Folder;
use SilverStripe\Forms\FieldList;
use SilverStripe\Assets\Filesystem;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Versioned\Versioned;
use LeKoala\Base\Forms\SmartUploadField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use LeKoala\Base\Forms\SmartSortableUploadField;

/**
 * Automatically publish files and images related to this data object
 *
 * @link https://github.com/bratiask/own-assets
 * @property \LeKoala\Base\Blocks\Block|\LeKoala\Base\News\NewsItem|\PortfolioItem|\LeKoala\Base\Extensions\SmartDataObjectExtension $owner
 */
class SmartDataObjectExtension extends DataExtension
{
    /**
     * Class names to consider for our getFiles* functions
     * @return array
     */
    protected function listFileTypes()
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
    protected function getAllFileRelations()
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
    protected function getHasOneFileRelations()
    {
        return $this->findFileRelations($this->owner->hasOne());
    }
    /**
     * Files in hasMany
     *
     * @return array
     */
    protected function getHasManyFileRelations()
    {
        return $this->findFileRelations($this->owner->hasMany());
    }
    /**
     * Files in manyMany
     *
     * @return array
     */
    protected function getManyManyFileRelations()
    {
        return $this->findFileRelations($this->owner->manyMany());
    }
    /**
     * Find file relations in a relation list
     *
     * @param array $arr list of relations
     * @return array
     */
    protected function findFileRelations($arr)
    {
        if (!$arr) {
            return [];
        }
        $fileTypes = $this->listFileTypes();
        $res = [];
        foreach ($arr as $name => $type) {
            if (\in_array($type, $fileTypes)) {
                $res[] = $name;
            }
        }
        return $res;
    }
    public function onAfterWrite()
    {
        $record = $this->owner;
        // If the owner is versioned, do no do this!
        $ownerIsVersioned = $record && $record->hasExtension(Versioned::class);
        if ($ownerIsVersioned) {
            return;
        }
        $relations = $this->getAllFileRelations();
        $changedFields = $this->owner->getChangedFields(true);
        foreach ($relations as $type => $names) {
            foreach ($names as $name) {
                if ($type == 'has_one') {
                    $field = $name . 'ID';
                    // Check state
                    if ($this->owner->$field) {
                        $file = $this->owner->$name();
                        if (!$file->isPublished()) {
                            $file->doPublish();
                        }
                    }
                    // Check if we need to delete previous file
                    if (isset($changedFields[$field])) {
                        $before = $changedFields[$field]['before'];
                        $after = $changedFields[$field]['after'];

                        // Clean old file
                        if ($before != $after) {
                            $oldFile = File::get()->byID($before);
                            if ($oldFile && $oldFile->ID) {
                                if ($oldFile->hasExtension(Versioned::class)) {
                                    $oldFile->deleteFromStage(Versioned::LIVE);
                                    $oldFile->deleteFromStage(Versioned::DRAFT);
                                } else {
                                    // Delete does not clean all stages :-(
                                    $oldFile->delete();
                                }
                            }
                        }
                    }
                } else {
                    foreach ($this->owner->$name() as $file) {
                        if (!$file->isPublished()) {
                            $file->doPublish();
                        }
                    }
                }
            }
        }
    }
    public function onBeforeDelete()
    {
        $folder = Folder::find_or_make($this->getFolderName());
        $filename = $folder->getFilename();
        if ($folder->hasExtension(Versioned::class)) {
            $folder->deleteFromStage(Versioned::LIVE);
            $folder->deleteFromStage(Versioned::DRAFT);
        } else {
            // Delete does not clean all stages :-(
            $folder->delete();
        }
        // Delete leaves ugly empty folders...
        if (defined('ASSETS_PATH')) {
            $assetsPath = ASSETS_PATH;

            $protected = $assetsPath . '/.protected/' . $filename;
            $public = $assetsPath . '/' . $filename;

            if (is_dir($protected)) {
                Filesystem::remove_folder_if_empty($protected);
                Filesystem::remove_folder_if_empty($public);
            }
        }
    }
    /**
     * The place where to store assets
     * We create a folder for each record to easily clean up after deletion
     *
     * @return string
     */
    public function getFolderName()
    {
        $class = ClassHelper::getClassWithoutNamespace($this->owner);
        return $class . '/' . $this->owner->ID;
    }
    public function updateCMSFields(FieldList $fields)
    {
        $config = $this->owner->config();
        $dataFields = $fields->dataFields();
        $manyManyFiles = $this->getManyManyFileRelations();
        $manyManyFilesExtraFields = $this->owner->manyManyExtraFields();

        foreach ($dataFields as $dataField) {
            $class = get_class($dataField);
            $fieldName = $dataField->getName();
            $newField = null;
            // Let's replace all base UploadFields with SmartUploadFields
            if ($class === UploadField::class) {
                $newField = new SmartUploadField($fieldName, $dataField->Title(), $dataField->getItems());
            }
            // Adjust GridFields
            if ($class === GridField::class) {
                // Let's replace many_many files grids with proper UploadFields
                if (in_array($fieldName, $manyManyFiles)) {
                    $extraFields = $manyManyFilesExtraFields[$fieldName] ?? [];
                    if (isset($extraFields['SortOrder'])) {
                        $newField = new SmartSortableUploadField($fieldName, $dataField->Title(), $dataField->getList());
                    } else {
                        $newField = new SmartUploadField($fieldName, $dataField->Title(), $dataField->getList());
                    }
                }
            }
            if ($newField) {
                // We should hide uploaders until we have an ID
                // if ($this->owner->ID) {
                    $fields->replaceField($fieldName, $newField);
                // } else {
                    // $fields->removeByName($fieldName);
                // }
            }
        }
    }
}
