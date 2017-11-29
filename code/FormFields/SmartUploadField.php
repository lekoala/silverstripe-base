<?php
namespace LeKoala\Base\FormFields;

use SilverStripe\ORM\SS_List;
use SilverStripe\AssetAdmin\Forms\UploadField as DefaultUploadField;


/**
 *
 */
class SmartUploadField extends DefaultUploadField
{
    public function __construct($name, $title = null, SS_List $items = null)
    {
        parent::__construct($name, $title, $items);
    }

    public function getFolderName()
    {
        // If no folder name is set, set a default one based on class name and relation name
        if ($this->folderName === false) {
            $record = $this->getRecord();
            if ($record) {
                $class = basename(str_replace('\\', '/', get_class($record)));
                $name = $this->getName();
                $this->folderName = $class . '/' . $name;
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
            \preg_match_all('/\[([a-zA-Z0-9_]+)\]/', $name, $matches);
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
