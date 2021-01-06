<?php

namespace LeKoala\Base\Test;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;
use LeKoala\Base\Extensions\IPExtension;
use LeKoala\Base\ORM\FieldType\DBCountry;

class Test_BaseModel extends DataObject implements TestOnly
{
    private static $db = [
        "Phone" => 'Varchar',
        "CountryCode" => DBCountry::class,
    ];
    private static $table_name = 'BaseModel';
    private static $extensions = [
        IPExtension::class,
    ];
}
