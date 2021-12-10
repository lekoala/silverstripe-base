<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DataObjectInterface;

/**
 * @link https://select2.org
 */
class Select2SingleField extends FastDropdownField
{
    use Select2;
    use ConfigurableField;

    /**
     * @param DataObject|DataObjectInterface $record The record to save into
     */
    public function saveInto(DataObjectInterface $record)
    {
        return parent::saveInto($record);
    }
}
