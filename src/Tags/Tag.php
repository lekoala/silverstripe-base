<?php

namespace LeKoala\Base\Tags;

use SilverStripe\ORM\DataObject;

/**
 * Class \LeKoala\Base\Tags\Tag
 *
 * @property ?string $URLSegment
 * @property ?string $Title
 * @mixin \LeKoala\CommonExtensions\URLSegmentExtension
 * @mixin \LeKoala\Base\Extensions\BaseDataObjectExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\CMS\Model\SiteTreeLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class Tag extends DataObject
{
    private static $table_name = 'Tag'; // When using namespace, specify table name
    private static $db = [
        "Title" => "Varchar(191)",
    ];
}
