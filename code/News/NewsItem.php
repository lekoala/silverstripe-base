<?php
namespace LeKoala\Base\News;

use SilverStripe\Assets\Image;
use LeKoala\Base\News\NewsPage;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FormAction;
use LeKoala\Base\News\NewsCategory;
use SilverStripe\ORM\FieldType\DBDate;
use LeKoala\Base\FormFields\SmartUploadField;
use LeKoala\Base\Actions\CustomAction;

/**
 * @property string $Title
 * @property string $Content
 */
class NewsItem extends DataObject
{
    private static $table_name = 'NewsItem'; // When using namespace, specify table name

    private static $db = [
        "Title" => "Varchar(191)",
        "Content" => "HTMLText",
        "Published" => DBDate::class,
    ];
    private static $has_one = [
        "Image" => Image::class,
        "Page" => NewsPage::class,
        "Category" => NewsCategory::class,
    ];
    private static $owns = [
        "Image"
    ];

    private static $default_sort = 'Published DESC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $Image = new SmartUploadField("Image");
        $Image->setIsMultiUpload(false);
        $fields->addFieldToTab('Root.Main', $Image);
        return $fields;
    }

    public function doSomething($data, $form, $controller)
    {

    }

    public function doPublish($data, $form, $controller)
    {
        $this->Published = date('Y-m-d H:i:s');
        $this->write();
    }

    public function doUnpublish($data, $form, $controller)
    {
        $this->Published = null;
        $this->write();
    }

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        if ($this->Published) {
            $action = new CustomAction("doUnpublish", "Unpublish");
            $actions->push($action);
        } else {
            $action = new CustomAction("doPublish", "Publish");
            $actions->push($action);
        }

        return $actions;
    }
}
