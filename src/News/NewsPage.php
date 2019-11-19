<?php

namespace LeKoala\Base\News;

use LeKoala\Base\News\NewsItem;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

/**
 * Class \LeKoala\Base\News\NewsPage
 *
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\News\NewsItem[] Items()
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\News\NewsCategory[] Categories()
 */
class NewsPage extends \Page
{
    private static $table_name = 'NewsPage'; // When using namespace, specify table name
    private static $db = [];
    private static $has_many = [
        "Items" => NewsItem::class,
        "Categories" => NewsCategory::class,
    ];
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $ItemsConfig = GridFieldConfig_RecordEditor::create();
        $Items = new GridField('Items', $this->fieldLabel('Items'), $this->Items(), $ItemsConfig);
        $fields->replaceField('Content', $Items);
        $CategoriesConfig = GridFieldConfig_RecordEditor::create();
        $Categories = new GridField('Categories', $this->fieldLabel('Categories'), $this->Categories(), $CategoriesConfig);
        $fields->addFieldsToTab('Root.Categories', $Categories);
        return $fields;
    }
    /**
     * @return DataList
     */
    public function DisplayedItems()
    {
        $list = $this->Items();
        // Exclude unpublished and future items
        $list = $list->where(NewsItem::defaultWhere());
        return $list;
    }
    /**
     * @return DataList
     */
    public function PopularItems($n = 3)
    {
        return $this->DisplayedItems()->sort('ViewCount DESC')->limit($n);
    }
    /**
     * @return DataList
     */
    public function LatestItems($n = 3)
    {
        return $this->DisplayedItems()->sort('Published DESC')->limit($n);
    }
}
