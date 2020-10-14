<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\SS_List;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\AssetAdmin\Forms\UploadField;
use LeKoala\Base\Blocks\Block;

/**
 * Improves the default uploader by uploading to a consistent default location
 * Records should really have an ID before uploading to ensure we know where to place the file
 * Otherwise, files might be uploaded and attached to nothing
 *
 * You can define on your DataObject a static config $image_sizes = ['Name' => [width, height]]
 * and it will be displayed in the description
 */
class SmartUploadField extends UploadField
{
    use BaseFileUploadReceiver;

    /**
     * Because who really use gifs and bmp?
     * @config
     * @var array
     */
    private static $default_image_ext = ['jpg', 'jpeg', 'png'];

    /**
     * Set a global limit for image size
     * This is useful to prevent users to upload large image that GD
     * won't be able to resize
     *
     * @config
     * @var string
     */
    private static $max_image_size = '3M';

    public function __construct($name, $title = null, SS_List $items = null)
    {
        parent::__construct($name, $title, $items);
    }

    public function saveInto(DataObjectInterface $record)
    {
        $fieldname = $this->getName();

        // If we store into json
        if ($record instanceof Block && strpos($fieldname, '[') !== false) {
            // Get details to save
            $idList = $this->getItemIDs();

            // Use array notation to allow loadDataFrom to work properly
            $record->setCastedField($fieldname, ['Files' => $idList]);
            return $this;
        }

        return parent::saveInto($record);
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

                // Because who wants images that crash everything?
                $maxSize = self::config()->max_image_size;
                if ($maxSize) {
                    $this->getValidator()->setAllowedMaxFileSize($maxSize);
                }
            }

            // Set a default description
            if (!$this->description) {
                $this->setDefaultDescription($relation, $record, $this->name);
            }
        }
        return parent::Field($properties);
    }

    public function getFolderName()
    {
        $record = $this->getRecord();
        if ($record) {
            // If no folder name is set, set a default one based on class name and relation name
            if ($this->folderName === false) {
                $this->folderName = $this->getDefaultFolderName();
            }
        }
        return parent::getFolderName();
    }

    public function setValue($value, $record = null)
    {
        return parent::setValue($value, $record);
    }
}
