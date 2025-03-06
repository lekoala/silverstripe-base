<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBVarchar;

class DBSafeVarchar extends DBVarchar
{
    public function setValue($value, $record = null, $markChanged = true)
    {
        if ($value !== null) {
            $value = strip_tags($value);
        }
        return parent::setValue($value, $record, $markChanged);
    }
}
