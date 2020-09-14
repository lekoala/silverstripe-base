<?php

namespace LeKoala\Base\News;

use SilverStripe\ORM\DB;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use LeKoala\Base\News\NewsPage;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use LeKoala\Base\News\NewsCategory;
use LeKoala\Base\Actions\CustomAction;
use LeKoala\Base\Forms\FlatpickrField;
use LeKoala\Base\Forms\InputMaskField;
use SilverStripe\ORM\FieldType\DBDate;
use LeKoala\Base\VideoEmbed\VideoEmbed;
use LeKoala\Base\Forms\SmartUploadField;
use LeKoala\Base\Forms\InputMaskDateField;
use SilverStripe\Security\Permission;

/**
 * Class \LeKoala\Base\News\NewsItem
 *
 * @property string $EmbedURL
 * @property string $URLSegment
 * @property string $Title
 * @property string $Content
 * @property string $Published
 * @property int $ViewCount
 * @property int $ImageID
 * @property int $FileID
 * @property int $PageID
 * @property int $CategoryID
 * @method \SilverStripe\Assets\Image Image()
 * @method \SilverStripe\Assets\File File()
 * @method \LeKoala\Base\News\NewsPage Page()
 * @method \LeKoala\Base\News\NewsCategory Category()
 * @method \SilverStripe\ORM\ManyManyList|\LeKoala\Base\Tags\Tag[] Tags()
 * @method \SilverStripe\ORM\ManyManyList|\SilverStripe\Assets\Image[] Images()
 * @mixin \LeKoala\Base\Extensions\URLSegmentExtension
 * @mixin \LeKoala\Base\Extensions\SmartDataObjectExtension
 * @mixin \LeKoala\Base\Tags\TaggableExtension
 * @mixin \LeKoala\Base\Extensions\SocialShareExtension
 * @mixin \LeKoala\Base\Extensions\EmbeddableExtension
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
        "File" => File::class,
        "Page" => NewsPage::class,
        "Category" => NewsCategory::class,
    ];
    private static $many_many = [
        "Images" => Image::class
    ];

    private static $owns = [
        "Image",
        "File",
        "Images",
    ];
    private static $summary_fields = [
        "Title", "Thumbnail.CMSThumbnail", "Published"
    ];
    private static $default_sort = 'Published DESC';
    public static $configure_fields = true;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if (self::$configure_fields) {
            $Image = new SmartUploadField("Image");
            $Image->setIsMultiUpload(false);
            $Image->setAllowedFileCategories("image/supported");
            $fields->addFieldToTab('Root.Main', $Image);
            $File = new SmartUploadField("File");
            $File->setIsMultiUpload(false);
            $fields->addFieldToTab('Root.Main', $File);
            $fields->makeFieldReadonly('ViewCount');
        }
        return $fields;
    }

    public function canView($member = null)
    {
        if ($this->Published) {
            return true;
        }
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function AbsoluteLink()
    {
        return Director::absoluteURL($this->Link());
    }

    public function Thumbnail()
    {
        if ($this->ImageID) {
            return $this->Image();
        }
        if ($this->CategoryID && $this->Category()->ImageID) {
            return $this->Category()->Image();
        }
        return false;
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

    public function forTemplate()
    {
        return $this->renderWith('LeKoala\Base\News\NewsItem');
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

    public function PrevItemID()
    {
        $map = array_keys($this->Page()->Items()->getIDList());
        $offset = array_search($this->ID, $map);
        return isset($map[$offset - 1]) ? $map[$offset - 1] : false;
    }

    public function PrevItem()
    {
        return NewsItem::get()->byID($this->PrevItemID());
    }

    public function NextItemID()
    {
        $map = array_keys($this->Page()->Items()->getIDList());
        $offset = array_search($this->ID, $map);
        return isset($map[$offset + 1]) ? $map[$offset + 1] : false;
    }

    public function NextItem()
    {
        return NewsItem::get()->byID($this->NextItemID());
    }
}
