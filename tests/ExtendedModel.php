<?php

namespace LeKoala\Base\Test;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;
use LeKoala\Base\Extensions\IPExtension;

class ExtendedModel extends DataObject implements TestOnly
{
    private static $table_name = 'ExtendedModel';
    private static $extensions = [
        IPExtension::class,
    ];
}
