<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBText;

class DBSafeText extends DBText
{
    public function setValue($value, $record = null, $markChanged = true)
    {
        if ($value !== null) {
            $value = strip_tags($value);
        }
        return parent::setValue($value, $record, $markChanged);
    }
}
