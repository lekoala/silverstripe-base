<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DataObjectInterface;

/**
 * @link https://tom-select.js.org/
 */
class TomSelectSingleField extends FastDropdownField implements AjaxPoweredField
{
    use TomSelect;

    /**
     * @param DataObject|DataObjectInterface $record The record to save into
     */
    public function saveInto(DataObjectInterface $record)
    {
        return parent::saveInto($record);
    }
}
