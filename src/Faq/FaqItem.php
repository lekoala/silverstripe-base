<?php

namespace LeKoala\Base\Faq;

use SilverStripe\ORM\DataObject;
use LeKoala\Base\Faq\FaqPage;
use LeKoala\Base\Faq\FaqCategory;

/**
 * Class \LeKoala\Base\Faq\FaqItem
 *
 * @property int $Sort
 * @property string $Title
 * @property string $Content
 * @property int $PageID
 * @property int $CategoryID
 * @method \LeKoala\Base\Faq\FaqPage Page()
 * @method \LeKoala\Base\Faq\FaqCategory Category()
 * @mixin \LeKoala\Base\Extensions\SortableExtension
 */
class FaqItem extends DataObject
{
    private static $table_name = 'FaqItem'; // When using namespace, specify table name
    private static $db = [
        "Title" => "Varchar(191)",
        "Content" => "HTMLText"
    ];
    private static $has_one = [
        "Page" => FaqPage::class,
        "Category" => FaqCategory::class,
    ];
}
