<?php
namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\ORM\Connect\MySQLDatabase;

/**
 * Json storage
 *
 * @link https://github.com/phptek/silverstripe-jsontext/blob/master/code/models/fieldtypes/JSONText.php
 */
class DBJson extends DBString
{
    /**
     * (non-PHPdoc)
     * @see DBField::requireField()
     */
    public function requireField()
    {
        $charset = Config::inst()->get(MySQLDatabase::class, 'charset');
        $collation = Config::inst()->get(MySQLDatabase::class, 'collation');

        $parts = [
            'datatype' => 'mediumtext',
            'character set'=> $charset,
            'collate'=> $collation,
            'arrayValue' => $this->arrayValue
        ];

        $values = [
            'type' => 'text',
            'parts' => $parts
        ];

        DB::require_field($this->tableName, $this->name, $values);
    }

    /**
     * @param string $title
     * @return \HiddenField
     */
    public function scaffoldSearchField($title = null)
    {
        return HiddenField::create($this->getName());
    }
    /**
     * @param string $title
     * @param string $params
     * @return \HiddenField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        return HiddenField::create($this->getName());
    }

    /**
     * @return mixed
     */
    public function decode()
    {
        if (!$this->value) {
            return false;
        }
        return json_decode($this->value);
    }

    /**
     * @return array
     */
    public function decodeArray()
    {
        if (!$this->value) {
            return [];
        }
        return json_decode($this->value, JSON_OBJECT_AS_ARRAY);
    }

    /**
     * @return string
     */
    public function pretty()
    {
        return json_encode(json_decode($this->value), JSON_PRETTY_PRINT);
    }

    public function saveInto($dataObject)
    {
        if ($this->value && is_array($this->value)) {
            $this->value = json_encode($this->value);
        }
        parent::saveInto($dataObject);
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return parent::setValue($value, $record, $markChanged);
    }

    public function prepValueForDB($value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return parent::prepValueForDB($value);
    }
}
