<?php
namespace LeKoala\Base\Tags;

use SilverStripe\ORM\DataObject;

/**
 * @property string $Title
 */
class Tag extends DataObject
{
    private static $table_name = 'Tag'; // When using namespace, specify table name

    private static $db = [
        "Title" => "Varchar(191)",
    ];

}
