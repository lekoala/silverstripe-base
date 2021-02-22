<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use LeKoala\CommonExtensions\IPExtension;
use LeKoala\GeoTools\FieldType\DBCountry;

class Test_BaseModel extends DataObject implements TestOnly
{
    private static $db = [
        "Phone" => "Varchar(51)",
        "CountryCode" => DBCountry::class,
    ];
    private static $table_name = 'BaseModel';
    private static $extensions = [
        IPExtension::class,
    ];
}
