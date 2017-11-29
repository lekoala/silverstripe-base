<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\DropdownField;

/**
 * @link https://select2.org
 */
class Select2SingleField extends DropdownField
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
