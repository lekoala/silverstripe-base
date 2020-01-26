<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBVarchar;
use LeKoala\Base\Forms\TimezoneDropdown;

/**
 * A tmiezone field
 */
class DBTimezone extends DBVarchar
{
    public function __construct($name = null, $options = [])
    {
        // The mysql.time_zone_name table has a Name column defined with 64 characters.
        // It makes sense to use VARCHAR(64) for storing this information; that matches the way the names are stored in the system.
        parent::__construct($name, 64, $options);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        $field = TimezoneDropdown::create($this->name, $title);
        return $field;
    }
}
