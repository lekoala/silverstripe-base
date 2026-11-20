<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBClassName;

/**
 * Store the class name of an optional polymorphic relation.
 */
class DBNullableClassName extends DBClassName
{
    /**
     * Leave an unset relation without a class name.
     *
     * @return null
     */
    public function getDefaultValue(): mixed
    {
        return null;
    }
}
