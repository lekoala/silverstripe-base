<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBForeignKey;
use LeKoala\Base\Forms\Select2SingleField;
use LeKoala\Base\Forms\FilePondField;

/**
 * Improves scaffolding by making better assumption than the defaults one
 */
class DBBetterForeignKey extends DBForeignKey
{
    public function scaffoldFormField($title = null, $params = null)
    {
        if (empty($this->object)) {
            return null;
        }
        $relationName = substr($this->name, 0, -2);
        $hasOneClass = DataObject::getSchema()->hasOneComponent(get_class($this->object), $relationName);
        if (empty($hasOneClass)) {
            return null;
        }
        $hasOneSingleton = singleton($hasOneClass);
        if ($hasOneSingleton instanceof File) {
            $field = Injector::inst()->create(FileHandleField::class, $relationName, $title);
            // $field = new FilePondField($relationName, $title);
            if ($hasOneSingleton instanceof Image) {
                $field->setAllowedFileCategories('image/supported');
            }
            return $field;
        }

        // Build selector / numeric field
        $titleField = $hasOneSingleton->hasField('Title') ? "Title" : "Name";
        $list = DataList::create($hasOneClass);
        // Don't scaffold a dropdown for large tables, as making the list concrete
        // might exceed the available PHP memory in creating too many DataObject instances
        if ($list->count() < 100) {
            $field = new DropdownField($this->name, $title, $list->map('ID', $titleField));
            $field->setHasEmptyDefault(true);
        } else {
            $field = new Select2SingleField($this->name, $title);
            $field->setAjaxWizard($hasOneClass);
        }
        return $field;
    }
}
