<?php
namespace LeKoala\Base\News;

use SilverStripe\Assets\Image;
use LeKoala\Base\News\NewsPage;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FormAction;
use LeKoala\Base\News\NewsCategory;
use SilverStripe\ORM\FieldType\DBDate;
use LeKoala\Base\Actions\CustomAction;
use LeKoala\Base\Forms\InputMaskField;
use LeKoala\Base\Forms\SmartUploadField;
use LeKoala\Base\Forms\InputMaskDateField;
use LeKoala\Base\Forms\FlatpickrField;
use SilverStripe\ORM\DB;

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
        "ViewCount" => "Int",
    ];
    private static $has_one = [
        "Image" => Image::class,
        "Page" => NewsPage::class,
        "Category" => NewsCategory::class,
    ];
    private static $owns = [
        "Image"
    ];
    private static $summary_fields = [
        "Title", "Image.CMSThumbnail", "Published"
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

    public static function defaultWhere()
    {
        return 'Published IS NOT NULL AND Published <= \'' . date('Y-m-d') . '\'';
    }

    public function updateViewCount()
    {
        $table = $this->baseTable();
        DB::query("UPDATE $table SET ViewCount = ViewCount+1 WHERE ID = " . $this->ID);
    }

    public function Year()
    {
        return date('Y', strtotime($this->Published));
    }

    public function Month()
    {
        return date('Y-m', strtotime($this->Published));
    }

    public function Link()
    {
        return $this->Page()->Link('read/' . $this->URLSegment);
    }

    public function updateURLSegment(&$segment)
    {
        $segment = date('Y-m-d', strtotime($this->Published)) . '-' . $segment;
    }

    public function Summary()
    {
        /* @var $obj HTMLText */
        $obj = $this->dbObject('Content');
        return $obj->Summary();
    }

    public function doPublish($data, $form, $controller)
    {
        $this->Published = date('Y-m-d H:i:s');
        $this->URLSegment = null; // Refresh
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

        if ($this->ID) {
            if ($this->Published) {
                $action = new CustomAction("doUnpublish", "Unpublish");
                $actions->push($action);
            } else {
                $action = new CustomAction("doPublish", "Publish");
                $actions->push($action);
            }
        }

        return $actions;
    }
}
