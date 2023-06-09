<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DataObjectInterface;
use LeKoala\FormElements\AjaxPoweredField;

/**
 * @link https://tom-select.js.org/
 * @deprecated
 * Use tom select instead
 */
class Select2SingleField extends FastDropdownField implements AjaxPoweredField
{
    use Select2;

    /**
     * @param DataObject|DataObjectInterface $record The record to save into
     */
    public function saveInto(DataObjectInterface $record)
    {
        return parent::saveInto($record);
    }
}
