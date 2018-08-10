<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 * Make a DataObject sortable with GridFieldOrderableRows
 *
 * @property \LeKoala\Base\Blocks\Block|\LeKoala\Base\Extensions\SortableExtension $owner
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
            // This allows you to define your own method if needed
            $this->owner->Sort = $this->owner->getNextSort();
        }
    }
    public function getNextSort()
    {
        $class = get_class($this->owner);
        $max = (int)$class::get()->max('Sort');
        return $max + 1;
    }
    public function PreviousInList($list)
    {
        return $list->where('Sort < ' . $this->owner->Sort)->sort('Sort DESC')->first();
    }
    public function NextInList($list)
    {
        return $list->where('Sort < ' . $this->owner->Sort)->sort('Sort ASC')->first();
    }
}
