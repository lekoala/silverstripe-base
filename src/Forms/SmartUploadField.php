<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Assets\File;
use SilverStripe\ORM\SS_List;
use SilverStripe\Assets\Image;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\AssetAdmin\Forms\UploadField;

/**
 * Improves the default uploader by uploading to a consistent default location
 * Records should really have an ID before uploading to ensure we know where to place the file
 * Otherwise, files might be uploaded and attached to nothing
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

    public function __construct($name, $title = null, SS_List $items = null)
    {
        parent::__construct($name, $title, $items);
    }

    public function getSchemaStateDefaults()
    {
        $state = parent::getSchemaStateDefaults();
        $urls = [];
        foreach ($this->getEncodedItems() as $item) {
            $urls[$item->ID] = $item->getAbsoluteURL();
        }
        $state['data']['urls'] = $urls;
        return $state;
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

    /**
     * Split Name[Input][Sub][Value] notation
     *
     * @param string $name
     * @return array
     */
    public static function extractNameParts($name)
    {
        if (strpos($name, '[') !== false) {
            $matches = null;
            preg_match_all('/\[([a-zA-Z0-9_]+)\]/', $name, $matches);
            $matches = $matches[1];
        } else {
            $matches = [$name];
        }
        return $matches;
    }

    public function setValue($value, $record = null)
    {
        return parent::setValue($value, $record);
    }
}
