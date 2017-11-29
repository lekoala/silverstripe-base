<?php
namespace LeKoala\Base\News;

use SilverStripe\ORM\DataObject;
use LeKoala\Base\News\NewsPage;
use LeKoala\Base\News\NewsItem;

/**
 * @property string $Title
 */
class NewsCategory extends DataObject
{
    private static $table_name = 'NewsCategory'; // When using namespace, specify table name

    private static $db = [
        "Title" => "Varchar(191)",
    ];
    private static $has_one = [
        "Page" => NewsPage::class,
    ];
    private static $has_many = [
        "Items" => NewsItem::class,
    ];

}
