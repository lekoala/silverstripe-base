<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;

/**
 * Polymorphic relations for records
 */
class HasRecordExtension extends DataExtension
{
    private static $db = [
        "RecordID" => "Int",
        "RecordClass" => "Varchar(191)",
    ];
    private static $indexes = [
        "RecordIndex" => ["RecordID", "RecordClass"]
    ];
}
