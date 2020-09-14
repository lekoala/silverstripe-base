<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\SS_List;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Folder;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FormField;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Forms\FileUploadReceiver;

/**
 * A base class that use file upload receiver
 */
abstract class BaseFileUploadField extends FormField implements FileHandleField
{
    use FileUploadReceiver;
    use BaseFileUploadReceiver;

    /**
     * Create a new file field.
     *
     * @param string $name The internal field name, passed to forms.
     * @param string $title The field label.
     * @param SS_List $items Items assigned to this field
     */
    public function __construct($name, $title = null, SS_List $items = null)
    {
        $this->constructFileUploadReceiver();

        // When creating new files, rename on conflict
        $this->getUpload()->setReplaceFile(false);

        parent::__construct($name, $title);
        if ($items) {
            $this->setItems($items);
        }

        // Fix null request
        if ($this->request instanceof NullHTTPRequest) {
            $this->request = Controller::curr()->getRequest();
        }
    }

    /**
     * Gets the upload folder name
     *
     * @return string
     */
    public function getFolderName()
    {
        return ($this->folderName !== false)
            ? $this->folderName
            : $this->getDefaultFolderName();
    }

    public function Field($properties = array())
    {
        $record = $this->getRecord();
        if ($record) {
            $relation = $record->getRelationClass($this->name);

            // Sadly, it's not always the case
            if ($relation == Image::class) {
                // Because who wants bmp and gif files?
                $allowedExtensions = $this->getAllowedExtensions();
                if (in_array('zip', $allowedExtensions)) {
                    $this->setAllowedExtensions(self::config()->default_image_ext);
                }
            }

            // Set a default description
            if (!$this->description) {
                $this->setDefaultDescription($relation);
            }
        }
        return parent::Field($properties);
    }


    public function getSchemaDataDefaults()
    {
        $defaults = parent::getSchemaDataDefaults();
        $defaults['data']['maxFiles'] = $this->getAllowedMaxFileNumber();
        $defaults['data']['multi'] = $this->getIsMultiUpload();
        $defaults['data']['parentid'] = $this->getFolderID();
        $defaults['data']['canUpload'] = $this->getUploadEnabled();
        $defaults['data']['canAttach'] = $this->getAttachEnabled();

        return $defaults;
    }

    public function getSchemaStateDefaults()
    {
        $state = parent::getSchemaStateDefaults();
        $state['data']['files'] = $this->getItemIDs();
        $state['value'] = $this->Value() ?: ['Files' => []];
        return $state;
    }

    /**
     * Get ID of target parent folder
     *
     * @return int
     */
    protected function getFolderID()
    {
        $folderName = $this->getFolderName();
        if (!$folderName) {
            return 0;
        }
        $folder = Folder::find_or_make($folderName);
        return $folder ? $folder->ID : 0;
    }

    /**
     * Check if allowed to upload more than one file
     *
     * @return bool
     */
    public function getIsMultiUpload()
    {
        if (isset($this->multiUpload)) {
            return $this->multiUpload;
        }
        // Guess from record
        $record = $this->getRecord();
        $name = $this->getName();

        // Disabled for has_one components
        if ($record && DataObject::getSchema()->hasOneComponent(get_class($record), $name)) {
            return false;
        }
        return true;
    }

    /**
     * Set upload type to multiple or single
     *
     * @param bool $bool True for multiple, false for single
     * @return $this
     */
    public function setIsMultiUpload($bool)
    {
        $this->multiUpload = $bool;
        return $this;
    }

    /**
     * Gets the number of files allowed for this field
     *
     * @return null|int
     */
    public function getAllowedMaxFileNumber()
    {
        return $this->allowedMaxFileNumber;
    }

    /**
     * Sets the number of files allowed for this field
     * @param $count
     * @return $this
     */
    public function setAllowedMaxFileNumber($count)
    {
        $this->allowedMaxFileNumber = $count;

        return $this;
    }

    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->setReadonly(true);
        return $clone;
    }

    public function performDisabledTransformation()
    {
        $clone = clone $this;
        $clone->setDisabled(true);
        return $clone;
    }

    /**
     * Checks if the number of files attached adheres to the $allowedMaxFileNumber defined
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $maxFiles = $this->getAllowedMaxFileNumber();
        $count = count($this->getItems());

        if ($maxFiles < 1 || $count <= $maxFiles) {
            return true;
        }
        $validator->validationError($this->getName(), '');
        return false;
    }

    /**
     * Check if uploading files is enabled
     *
     * @return bool
     */
    public function getUploadEnabled()
    {
        return $this->uploadEnabled;
    }

    /**
     * Set if uploading files is enabled
     *
     * @param bool $uploadEnabled
     * @return $this
     */
    public function setUploadEnabled($uploadEnabled)
    {
        $this->uploadEnabled = $uploadEnabled;
        return $this;
    }

    /**
     * Check if attaching files is enabled
     *
     * @return bool
     */
    public function getAttachEnabled()
    {
        return $this->attachEnabled;
    }

    /**
     * Set if attaching files is enabled
     *
     * @param bool $attachEnabled
     * @return UploadField
     */
    public function setAttachEnabled($attachEnabled)
    {
        $this->attachEnabled = $attachEnabled;
        return $this;
    }

    public function getAttributes()
    {
        $attributes = array(
            'class' => $this->extraClass(),
            'type' => 'file',
            'multiple' => $this->getIsMultiUpload(),
            'id' => $this->ID(),
            'data-schema' => json_encode($this->getSchemaData()),
            'data-state' => json_encode($this->getSchemaState()),
        );

        $attributes = array_merge($attributes, $this->attributes);

        $this->extend('updateAttributes', $attributes);

        return $attributes;
    }
}
