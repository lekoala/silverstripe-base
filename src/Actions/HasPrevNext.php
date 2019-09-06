<?php

namespace LeKoala\Base\Actions;

use LeKoala\Base\Extensions\SortableExtension;

/**
 * Implements prev/next behaviour
 */
trait HasPrevNext
{
    /**
     * @return DataObject
     */
    public function PrevRecord()
    {
        $class = get_class($this);
        if ($this->hasExtension(SortableExtension::class)) {
            return $this->owner->PreviousInList($class::get());
        }
        return $class::get()->filter('ID:GreaterThan', $this->ID)->sort('ID DESC')->first();
    }

    /**
     * @return DataObject
     */
    public function NextRecord()
    {
        $class = get_class($this);
        if ($this->hasExtension(SortableExtension::class)) {
            return $this->owner->NextInList($class::get());
        }
        return $class::get()->filter('ID:LessThan', $this->ID)->sort('ID ASC')->first();
    }
}
