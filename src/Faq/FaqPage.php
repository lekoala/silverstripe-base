<?php

namespace LeKoala\Base\Faq;

use LeKoala\Base\Faq\FaqItem;
use LeKoala\Base\Faq\FaqCategory;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\CommonExtensions\SortableExtension;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

/**
 * Class \LeKoala\Base\Faq\FaqPage
 *
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Faq\FaqItem[] Items()
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Faq\FaqCategory[] Categories()
 * @mixin \LeKoala\Base\Extensions\BaseDataObjectExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\CMS\Model\SiteTreeLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class FaqPage extends \Page
{
    private static $table_name = 'FaqPage'; // When using namespace, specify table name
    private static $db = [];
    private static $has_many = [
        "Items" => FaqItem::class,
        "Categories" => FaqCategory::class,
    ];
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $ItemsConfig = GridFieldConfig_RecordEditor::create();

        $singl = singleton(FaqItem::class);
        if ($singl->hasExtension(SortableExtension::class)) {
            $ItemsConfig->addComponent(new GridFieldOrderableRows());
        }

        $Items = new GridField('Items', $this->fieldLabel('Items'), $this->Items(), $ItemsConfig);
        $fields->addFieldToTab('Root.Items', $Items);
        $CategoriesConfig = GridFieldConfig_RecordEditor::create();

        $singl = singleton(FaqCategory::class);
        if ($singl->hasExtension(SortableExtension::class)) {
            $CategoriesConfig->addComponent(new GridFieldOrderableRows());
        }

        $Categories = new GridField('Categories', $this->fieldLabel('Categories'), $this->Categories(), $CategoriesConfig);
        $fields->addFieldToTab('Root.Categories', $Categories);
        return $fields;
    }
}
