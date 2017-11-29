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
    ];

    public function index()
    {
        // Use non namespaced name
        return $this->renderWith(['NewsPage', 'Page']);
    }

    public function DisplayedItems() {
        $list = $this->data()->Items();
        $paginatedList = new PaginatedList($list);
        return $list;
    }
}
