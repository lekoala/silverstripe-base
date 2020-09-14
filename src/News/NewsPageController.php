<?php

namespace LeKoala\Base\News;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataList;
use LeKoala\Base\News\NewsItem;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\GroupedList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\View\Requirements;

/**
 * Class \LeKoala\Base\News\NewsPageController
 *
 * @property \LeKoala\Base\News\NewsPage dataRecord
 * @method \LeKoala\Base\News\NewsPage data()
 * @mixin \LeKoala\Base\News\NewsPage dataRecord
 */
class NewsPageController extends \PageController
{
    private static $allowed_actions = [
        "index",
        "read",
        "category",
        "archives",
        "tags",
        "search",
    ];
    /**
     * @var DataList
     */
    protected $list;

    public function init()
    {
        parent::init();
        $this->list = $this->DisplayedItems();

        /*
        LeKoala\Base\News\NewsPageController:
          theme_files: true
        */
        if ($this->config()->theme_files) {
            Requirements::themedCSS('news.css');
            Requirements::themedJavascript('news.js');
        }
    }

    public function index(HTTPRequest $request = null)
    {
        return $this->render();
    }

    public function search()
    {
        $ID = $this->getRequest()->getVar('q');
        if ($ID) {
            // Use array notation for parameters to make sure it's properly passed as params
            $this->list = $this->list->where(["Title LIKE ?" => ['%' . $ID . '%']]);
        }
        return $this->render(['Query' => $ID]);
    }

    public function archives()
    {
        $ID = $this->getRequest()->param('ID');
        if ($ID) {
            // Use array notation for parameters to make sure it's properly passed as params
            $this->list = $this->list->where(["Published LIKE ?" => [$ID . '%']]);
        }
        return $this->render();
    }

    public function category()
    {
        $ID = $this->getRequest()->param('ID');
        $Category = null;
        if ($ID) {
            $Category = NewsCategory::get()->filter('URLSegment', $ID)->first();
            if ($Category) {
                // Use array notation for parameters to make sure it's properly passed as params
                $this->list = $this->list->where(["CategoryID = ?" => [$Category->ID]]);
            }
        }
        return $this->render(['CurrentCategory' => $Category]);
    }

    public function tags()
    {
        $ID = $this->getRequest()->param('ID');
        if ($ID) {
            $Tag = $this->TagsList()->filter('URLSegment', $ID)->first();
            if ($Tag) {
                $this->list = $this->list->filter('Tags.ID', $Tag->ID);
            }
        }
        return $this->render();
    }

    public function read()
    {
        $ID = $this->getRequest()->param('ID');
        if (!$ID) {
            return $this->httpError(404);
        }
        $Item = NewsItem::get()->filter('URLSegment', $ID)->first();
        if (!$Item) {
            return $this->httpError(404);
        }
        return $this->render(['Item' => $Item]);
    }

    /**
     * @return PaginatedList
     */
    public function PaginatedList()
    {
        $paginatedList = new PaginatedList($this->list, $this->getRequest());
        $paginatedList->setPageLength(6);
        return $paginatedList;
    }

    /**
     * @return ArrayList
     */
    public function YearsList()
    {
        $ID = null;
        if ($this->action == 'archives') {
            $ID = $this->getRequest()->param('ID');
        }
        $Singleton = NewsItem::singleton();
        $table = $Singleton->baseTable();
        $years = array_unique(DB::prepared_query("SELECT YEAR(Published) FROM $table WHERE Published IS NOT NULL AND Published <= ?", [
            DBDatetime::now()->Format(DBDatetime::ISO_DATETIME)
        ])->column());
        $result = ArrayList::create();
        foreach ($years as $year) {
            $result->push(ArrayData::create([
                'Title' => $year,
                'Link' => $this->Link() . 'archives/' . $year,
                'Current' => $ID == $year ? true : false,
            ]));
        }
        return $result;
    }

    /**
     * @return ArrayList
     */
    public function ArchivesList()
    {
        $format = '%Y-%m';
        $Published = DB::get_conn()->formattedDatetimeClause('"Published"', $format);
        $fields = [
            'Published' => $Published,
            'Total' => "COUNT('\"Published\"')"
        ];
        $Singleton = NewsItem::singleton();
        $table = $Singleton->baseTable();
        $query = SQLSelect::create($fields, $table)
            ->addGroupBy($Published)
            ->addOrderBy('"Published" DESC')
            ->addWhere('Published IS NOT NULL')
            ->addWhere(['"Published" <= ?' => DBDatetime::now()->Format(DBDatetime::ISO_DATETIME)]);
        $posts = $query->execute();
        $result = ArrayList::create();
        foreach ($posts as $post) {
            $date = DBDate::create();
            $date->setValue(strtotime($post['Published']));
            $year = $date->Format('y');
            $month = $date->Format('MM');
            $title = ucwords($date->Format('MMMM y')) . ' (' . $post['Total'] . ')';
            $result->push(ArrayData::create([
                'Title' => $title,
                'Link' => $this->Link() . 'archives/' . $post['Published'],
            ]));
        }
        return $result;
    }

    /**
     * @return GroupedList
     */
    public function GroupedList()
    {
        $list = $this->DisplayedItems();
        $groupedList = new GroupedList($list);
        return $groupedList;
    }

    /**
     * @return DataList|Tag[]
     */
    public function TagsList()
    {
        $list = $this->DisplayedItems()->relation('Tags');
        return $list;
    }

    /**
     * @return DataList|NewsCategory[]
     */
    public function CategoriesList()
    {
        $list = NewsCategory::get();
        return $list;
    }
}
