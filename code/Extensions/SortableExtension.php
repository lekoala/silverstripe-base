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
            $class = get_class($this->owner);
            $this->owner->Sort = $class::get()->max('Sort') + 1;
        }
    }
    public function PreviousInList($list)
    {
        return $list->where('Sort < ' . $this->owner->Sort)->sort('Sort DESC')->first();
    }
    public function NextInList($list)
    {
        return $list->where('Sort < ' . $this->Sort)->sort('Sort ASC')->first();
    }
}
