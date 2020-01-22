<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBPolymorphicForeignKey;

/**
 * Allow null class by default is no relation is set
 */
class DBNullablePolymorphicForeignKey extends DBPolymorphicForeignKey
{
    private static $composite_db = array(
        'ID' => 'Int',
        'Class' => "DBNullableClassName('" . DataObject::class . "', ['index' => false])"
    );
}
