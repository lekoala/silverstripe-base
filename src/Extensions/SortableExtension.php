<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 * Make a DataObject sortable with GridFieldOrderableRows
 *
 * @property \PortfolioCategory|\PortfolioItem|\LeKoala\Base\Blocks\Block|\LeKoala\Base\Faq\FaqCategory|\LeKoala\Base\Faq\FaqItem|\LeKoala\Base\Extensions\SortableExtension $owner
 * @property int $Sort
 */
class SortableExtension extends DataExtension
{
    private static $db = [
        "Sort" => "Int",
    ];
    private static $default_sort = 'Sort ASC';
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('Sort');
    }
    public function onBeforeWrite()
    {
        if (!$this->owner->Sort) {
            $this->owner->Sort = $this->owner->getNextSort();
        }
    }
    /**
     * This allows you to define your own method if needed
     * Like when you have page dependent data objects that shouldn't use a global sort
     * Or if you want to sort by a given multiple to allow inserts later on
     * @return int
     */
    public function getNextSort()
    {
        $class = get_class($this->owner);
        $max = (int) $class::get()->max('Sort');
        return $max + 1;
    }

    /**
     * @param DataList $list
     * @return DataObject
     */
    public function PreviousInList($list)
    {
        return $list->where('Sort < ' . $this->owner->Sort)->sort('Sort DESC')->first();
    }

    /**
     * @param DataList $list
     * @return DataObject
     */
    public function NextInList($list)
    {
        return $list->where('Sort > ' . $this->owner->Sort)->sort('Sort ASC')->first();
    }
}
