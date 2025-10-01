<?php

namespace LeKoala\Base\ORM\FieldType;

use LeKoala\Base\Helpers\StringHelper;
use SilverStripe\ORM\FieldType\DBVarchar;

/**
 * Safe by default without a need for escaping
 * HTML Attributes do require a custom call for unquoted attributes support
 */
class DBSafeVarchar extends DBVarchar
{
    public function setValue($value, $record = null, $markChanged = true)
    {
        if ($value !== null) {
            $value = strip_tags($value);
        }
        return parent::setValue($value, $record, $markChanged);
    }

    public function HTMLATT()
    {
        return StringHelper::escHtmlAtt($this->RAW());
    }
}
