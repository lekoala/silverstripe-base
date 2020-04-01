<?php
namespace LeKoala\Base\Tags;

use SilverStripe\ORM\DataObject;

/**
 * Class \LeKoala\Base\Tags\Tag
 *
 * @property string $URLSegment
 * @property string $Title
 * @mixin \LeKoala\Base\Extensions\URLSegmentExtension
 * @mixin \LeKoala\Base\Extensions\FluentCascadeDeleteExtension
 * @mixin \TractorCow\Fluent\Extension\FluentExtension
 */
class Tag extends DataObject
{
    private static $table_name = 'Tag'; // When using namespace, specify table name
    private static $db = [
        "Title" => "Varchar(191)",
    ];
}
