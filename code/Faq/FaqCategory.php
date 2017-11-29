<?php
namespace LeKoala\Base\Faq;

use SilverStripe\ORM\DataObject;
use LeKoala\Base\Faq\FaqPage;
use LeKoala\Base\Faq\FaqItem;

/**
 * @property string $Title
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
