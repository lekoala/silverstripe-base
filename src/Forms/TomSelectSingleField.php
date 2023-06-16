<?php

namespace LeKoala\Base\Forms;

use LeKoala\Base\Forms\FastDropdownField;
use SilverStripe\ORM\DataObjectInterface;
use LeKoala\FormElements\AjaxPoweredField;

/**
 * @link https://tom-select.js.org/
 * @deprecated use FormElements
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
