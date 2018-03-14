<?php
namespace LeKoala\Base\News;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\News\NewsPage;
use LeKoala\Base\News\NewsItem;
/**
 * Class \LeKoala\Base\News\NewsCategory
 *
 * @property string $Title
 * @property int $PageID
 * @method \LeKoala\Base\News\NewsPage Page()
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\News\NewsItem[] Items()
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