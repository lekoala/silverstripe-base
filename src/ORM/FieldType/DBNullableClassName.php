<?php

namespace LeKoala\Base\ORM\FieldType;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBClassName;

/**
 */
class DBNullableClassName extends DBClassName
{

    /**
     * Create a new DBClassName field
     *
     * @param string      $name      Name of field
     * @param string|null $baseClass Optional base class to limit selections
     * @param array       $options   Optional parameters for this DBField instance
     */
    public function __construct($name = null, $baseClass = null, $options = [])
    {
        parent::__construct($name, $baseClass, $options);
        $this->setDefault(null);
    }

    /**
     * @return void
     */
    public function requireField()
    {
        $enums = $this->getEnumObsolete();

        if (empty($enums)) {
            throw new Exception("Enum list is empty, ensure base class is set properly");
        }

        $parts = array(
            'datatype' => 'enum',
            'enums' => $enums,
            'character set' => 'utf8',
            'collate' => 'utf8_general_ci',
            'default' => $this->getDefault(),
            'table' => $this->getTable(),
            'arrayValue' => $this->arrayValue
        );

        $values = array(
            'type' => 'enum',
            'parts' => $parts
        );


        DB::require_field($this->getTable(), $this->getName(), $values);
    }


    public function getDefault()
    {
        return null;
    }
}
