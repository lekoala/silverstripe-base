<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\Assets\File;
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
 * @property \PortfolioCategory|\PortfolioItem|\TimelineItem|\LeKoala\Base\Blocks\Block|\LeKoala\Base\News\NewsItem|\LeKoala\Base\Extensions\SmartDataObjectExtension $owner
 */
class SmartDataObjectExtension extends DataExtension
{
    public function onAfterWrite()
    {
        $record = $this->owner;
        // If the owner is versioned, do not do this!
        $ownerIsVersioned = $record && $record->hasExtension(Versioned::class);
        if ($ownerIsVersioned) {
            return;
        }
        // This is taken care of in BaseDataObjectExtension::publishOwnAssets
        // $relations = $this->owner->getAllFileRelations();
        // $changedFields = $this->owner->getChangedFields(true);
        // foreach ($relations as $type => $names) {
        //     foreach ($names as $name) {
        //         if ($type == 'has_one') {
        //             $field = $name . 'ID';
        //             // Check state
        //             if ($this->owner->$field) {
        //                 $file = $this->owner->$name();
        //                 if (!$file->isPublished() && $file->ID) {
        //                     $file->doPublish();
        //                 }
        //             }
        //         } else {
        //             foreach ($this->owner->$name() as $file) {
        //                 if (!$file->isPublished() && $file->ID) {
        //                     $file->doPublish();
        //                 }
        //             }
        //         }
        //     }
        // }
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
        $manyManyFiles = $this->owner->getManyManyFileRelations();
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
