<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBText;
use LeKoala\Base\Helpers\StringHelper;

/**
 * Safe by default without a need for escaping
 * HTML Attributes do require a custom call for unquoted attributes support
 */
class DBSafeText extends DBText
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
