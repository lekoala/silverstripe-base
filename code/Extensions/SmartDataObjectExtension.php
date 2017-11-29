<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\File;

/**
 * 
 */
class SmartDataObjectExtension extends DataExtension
{
    protected function listFileTypes()
    {
        return [
            Image::class,
            File::class,
        ];
    }

    protected function getAllFileRelations()
    {
        return [
            'has_one' => $this->getHasOneFileRelations(),
            'has_many' => $this->getHasManyFileRelations(),
            'many_many' => $this->getManyManyFileRelations(),
        ];
    }

    protected function getHasOneFileRelations()
    {
        $config = $this->owner->config();
        $rel = $config->has_one;
        return $this->findFileRelations($rel);
    }

    protected function getHasManyFileRelations()
    {
        $config = $this->owner->config();
        $rel = $config->has_many;
        return $this->findFileRelations($rel);
    }

    protected function getManyManyFileRelations()
    {
        $config = $this->owner->config();
        $rel = $config->many_many;
        return $this->findFileRelations($rel);
    }

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
        //TODO: only do this if not using Versioned extension
        $relations = $this->getAllFileRelations();

        foreach ($relations as $type => $names) {
            foreach ($names as $name) {
                if ($type == 'has_one') {
                    $field = $name . 'ID';
                    if ($this->owner->$field) {
                        $file = $this->owner->$name();
                        if (!$file->isPublished()) {
                            $file->doPublish();
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

    public function updateCMSFields(FieldList $fields)
    {
        $dataFields = $fields->dataFields();

        $manyManyFiles = $this->getManyManyFileRelations();

        foreach ($dataFields as $dataField) {
            $class = get_class($dataField);

            // Let's replace all base UploadFields with SmartUploadFields
            if ($class === UploadField::class) {
                $newField = new \LeKoala\Base\FormFields\SmartUploadField($dataField->getName(), $dataField->Title(), $dataField->getItems());
                $fields->replaceField($dataField->getName(), $newField);
            }

            // Adjust GridFields
            if ($class === GridField::class) {
                // Let's replace many_many files grids with proper UploadFields
                if (\in_array($dataField->getName(), $manyManyFiles)) {
                    $newField = new \LeKoala\Base\FormFields\SmartUploadField($dataField->getName(), $dataField->Title(), $dataField->getList());
                    $fields->replaceField($dataField->getName(), $newField);
                }
            }
        }
    }

}