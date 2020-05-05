<?php

namespace LeKoala\Base\Faq;

use SilverStripe\ORM\DataObject;
use LeKoala\Base\Faq\FaqPage;
use LeKoala\Base\Faq\FaqItem;

/**
 * Class \LeKoala\Base\Faq\FaqCategory
 *
 * @property int $Sort
 * @property string $Title
 * @property int $PageID
 * @method \LeKoala\Base\Faq\FaqPage Page()
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Faq\FaqItem[] Items()
 * @mixin \LeKoala\Base\Extensions\SortableExtension
 */
class FaqCategory extends DataObject
{
    private static $table_name = 'FaqCategory'; // When using namespace, specify table name
    private static $db = [
        "Title" => "Varchar(191)",
    ];
    private static $has_one = [
        "Page" => FaqPage::class,
    ];
    private static $has_many = [
        "Items" => FaqItem::class,
    ];
}
