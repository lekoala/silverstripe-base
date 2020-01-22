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
 * Internally, the value is represented as a string (for consistency since we extend DBString)
 * TODO: investigate if it's worth it to use an array for internal state
 *
 * @link https://github.com/phptek/silverstripe-jsontext/blob/master/code/ORM/FieldType/JSONText.php
 * @link https://mariadb.com/resources/blog/json-mariadb-102
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
            'character set' => $charset,
            'collate' => $collation,
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
     * @return array
     */
    public function toArray()
    {
        return $this->decodeArray();
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

    /**
     * Add a value
     *
     * @link https://stackoverflow.com/questions/7851590/array-set-value-using-dot-notation
     * @param string|array $key
     * @param string $value
     * @return $this
     */
    public function addValue($key, $value)
    {
        $currentValue = $this->decodeArray();

        if (!is_array($key)) {
            $key = [$key];
        }
        $arr = &$currentValue;
        foreach ($key as $idx) {
            if (!isset($arr[$idx])) {
                $arr[$idx] = [];
            }
            $arr = &$arr[$idx];
        }
        $arr = $value;
        return $this->setValue($currentValue);
    }

    /**
     * Internally, the value is always a json string
     *
     * @param mixed $value
     * @param DataObject $record
     * @param boolean $markChanged
     * @return $this
     */
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

    /**
     * We return false because we can accept array and convert it to string
     * @return boolean
     */
    public function scalarValueOnly()
    {
        return false;
    }

    /**
     * Search multiple values in an array like store
     *
     * TODO: support proper json function if they are supported
     *
     * @param string $field
     * @param array $values
     * @return string
     */
    public static function sqlInArray($field, $values)
    {
        $gen = '';
        foreach ($values as $val) {
            $gen .= "(?=.*$val)";
        }
        $sql = "$field RLIKE '$gen'";
        return $sql;
    }
}
