<?php
namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DataObject;

/**
 * Set source dynamically if record is not present
 */
trait Select2Source
{
    public function setValue($value, $data = null)
    {
        // For ajax, we need to add the option to the list
        if ($value && $this->getAjaxClass()) {
            $class = $this->getAjaxClass();
            $record = DataObject::get_by_id($class, $value);
            $this->addRecordToSource($record);
        }
        $result = parent::setValue($value, $data);
        return $result;
    }

    public function setSubmittedValue($value, $data = null)
    {
        return $this->setValue($value, $data);
    }

    /**
     * Add a record to the source
     *
     * Useful for ajax scenarios where the list is not prepopulated but still needs to display
     * something on first load
     *
     * @param DataObject $record
     * @return boolean true if the record has been added, false otherwise
     */
    public function addRecordToSource($record)
    {
        if (!$record) {
            return false;
        }
        $source = $this->getSource();
        // It's already in the source
        if (isset($source[$record->ID])) {
            return false;
        }
        $row = [$record->ID => $record->getTitle()];
        // If source is empty, it's not going to be merged properly
        if (!empty($source)) {
            $source = array_merge($row, $source);
        } else {
            $source = $row;
        }
        $this->setSource($source);
        return true;
    }
}
