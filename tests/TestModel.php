<?php

namespace LeKoala\Base\Test;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;
use LeKoala\Base\Extensions\IPExtension;
use LeKoala\Base\ORM\FieldType\DBPhone;
use LeKoala\Base\ORM\FieldType\DBCountry;

class TestModel extends DataObject implements TestOnly
{
    private static $db = [
        "Phone" => DBPhone::class,
        "Country" => DBCountry::class,
    ];
    private static $table_name = 'TestModel';
    private static $extensions = [
        IPExtension::class,
    ];
}
