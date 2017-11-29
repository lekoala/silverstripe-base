<?php
namespace LeKoala\Base\Faq;

use SilverStripe\ORM\DataObject;
use LeKoala\Base\Faq\FaqPage;
use LeKoala\Base\Faq\FaqCategory;

/**
 * @property string $Title
 * @property string $Content
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
