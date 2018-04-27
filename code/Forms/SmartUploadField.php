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
    public function __construct($name, $title = null, SS_List $items = null)
    {
        parent::__construct($name, $title, $items);
    }
    public function getDescription()
    {
        if (!$this->description) {
            $record = $this->getRecord();
            if ($record) {
                $desc = '';
                $relation = $record->getRelationClass($this->name);
                $size = File::format_size($this->getValidator()->getAllowedMaxFileSize());
                switch ($relation) {
                    case Image::class:
                        $desc = _t('SmartUploadField.MAXSIZE', 'Max file size: {size}', ['size' => $size]);
                        $desc .= '; ';
                        $desc .= _t('SmartUploadField.MAXRESOLUTION', 'Max resolution: 2048x2048px; Allowed extensions: {ext}', array('ext' => implode(',', $this->getAllowedExtensions())));
                        break;
                    default:
                        $desc = _t('SmartUploadField.MAXSIZE', 'Max file size: {size}', ['size' => $size]);
                        break;
                }
                return $desc;
            }
        }
        return $this->description;
    }

    public function getFolderName()
    {
        $record = $this->getRecord();
        if ($record) {
            // If no folder name is set, set a default one based on class name and relation name
            if ($this->folderName === false) {
                if ($this->record->hasMethod('getFolderName')) {
                    $this->folderName = $this->record->getFolderName();
                } else {
                    $class = ClassHelper::getClassWithoutNamespace($record);
                    $name = $this->getName();
                    $this->folderName = $class . '/' . $name;
                }
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
