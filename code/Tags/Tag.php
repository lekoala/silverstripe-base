<?php
namespace LeKoala\Base\Tags;
use SilverStripe\ORM\DataObject;
/**
 * Class \LeKoala\Base\Tags\Tag
 *
 * @property string $Title
 * @property string $URLSegment
 * @mixin \LeKoala\Base\Extensions\URLSegmentExtension
 */
class Tag extends DataObject
{
    private static $table_name = 'Tag'; // When using namespace, specify table name
    private static $db = [
        "Title" => "Varchar(191)",
    ];
}