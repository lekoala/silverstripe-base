<?php

namespace LeKoala\Base\News;

use SilverStripe\Assets\Image;
use LeKoala\Base\News\NewsItem;
use LeKoala\Base\News\NewsPage;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Forms\SmartUploadField;

/**
 * Class \LeKoala\Base\News\NewsCategory
 *
 * @property string $URLSegment
 * @property string $Title
 * @property int $ImageID
 * @property int $PageID
 * @method \SilverStripe\Assets\Image Image()
 * @method \LeKoala\Base\News\NewsPage Page()
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\News\NewsItem[] Items()
 * @mixin \LeKoala\Base\Extensions\URLSegmentExtension
 */
class NewsCategory extends DataObject
{
    private static $table_name = 'NewsCategory'; // When using namespace, specify table name
    private static $db = [
        "Title" => "Varchar(191)",
    ];
    private static $has_one = [
        "Image" => Image::class,
        "Page" => NewsPage::class,
    ];
    private static $owns = [
        "Image",
    ];
    private static $has_many = [
        "Items" => NewsItem::class,
    ];
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $Image = new SmartUploadField("Image");
        $Image->setIsMultiUpload(false);
        $Image->setAllowedFileCategories("image/supported");
        $fields->addFieldToTab('Root.Main', $Image);
        return $fields;
    }
    public function Link()
    {
        return $this->Page()->Link('category/' . $this->URLSegment);
    }
}
