<?php
namespace LeKoala\Base\News;

use SilverStripe\ORM\PaginatedList;


/**
 *
 */
class NewsPageController extends \PageController
{
    private static $allowed_actions = [
        "index",
        "read",
    ];

    public function index()
    {
        // Use non namespaced name
        return $this->renderWith(['NewsPage', 'Page']);
    }

    public function read()
    {
         // Use non namespaced name
        return $this->renderWith(['NewsPage', 'Page']);
    }

    public function DisplayedItems()
    {
        $list = $this->data()->Items();

        // Exclude unpublished and future items
        $list = $list->where('Published IS NOT NULL AND Published <= \'' . date('Y-m-d') . '\'');

        return $list;
    }

    public function PaginatedList()
    {
        $paginatedList = new PaginatedList($this->DisplayedItems(), $this->getRequest());
        $paginatedList->setPageLength(6);
        return $paginatedList;
    }

    public function PopularItems($n = 3)
    {
        return $this->DisplayedItems()->sort('ViewCount DESC')->limit($n);
    }

    public function ArchivesList() {

    }
}
