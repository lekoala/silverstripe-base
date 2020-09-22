<?php

namespace LeKoala\Base\Forms\GridField;

use Exception;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

/**
 * Ease of use for day to day coding relative to GridField usage
 *
 * - Allow coloring row classes based on sublying model
 * - Helper methods for field labels
 * - Data columns helpers
 *
 * @property \LeKoala\Base\Forms\SimpleHasOneButtonField|\LeKoala\Base\Forms\FullGridField\FullGridField|\SilverShop\HasOneField\HasOneButtonField|\SilverStripe\Forms\GridField\GridField|\LeKoala\Base\Forms\GridField\GridFieldExtension $owner
 */
class GridFieldExtension extends Extension
{
    /**
     * See admin.css
     *
     * green,blue,amber,red
     *
     * @param string $classes
     * @param int $total
     * @param int $index
     * @param DataObject $record
     * @return void
     */
    public function updateNewRowClasses(&$classes, $total, $index, $record)
    {
        // Use the extension point to forward the color decision to the record
        if ($record->hasMethod('getRowClass')) {
            $class = $record->getRowClass($total, $index, $record);
            if ($class) {
                $classes[] = $class;
            }
        }
    }

    /**
     * Turn a list of fields into a consistent array with labels
     *
     * @param string $fields
     * @return array
     */
    public function fieldLabels($fields)
    {
        $class = $this->owner->getModelClass();
        $singl = $class::singleton();

        $i = 0;
        foreach ($fields as $index => $label) {
            if (is_numeric($index)) {
                $key = $label;
                $parts = explode('.', $key);
                $label = $singl->fieldLabel($parts[0]);
            } else {
                $key = $index;
                $index = $i;
            }
            $arr[$key] = $label;
            $i++;
        }

        return $arr;
    }

    /**
     * @return GridFieldDataColumns
     */
    public function getDataColumns()
    {
        $cols = $this->owner->getConfig()->getComponentByType(GridFieldDataColumns::class);
        if (!$cols) {
            throw new Exception('GridFieldDataColumns does not exist on this GridField');
        }
        return $cols;
    }

    /**
     * @return array
     */
    public function getDisplayFields()
    {
        return $this->getDataColumns()->getDisplayFields($this->owner);
    }

    /**
     * Shorhand for setting field labels
     *
     * @param array $displayFields
     * @return GridField
     */
    public function setDisplayFields($displayFields)
    {
        $this->getDataColumns()->setDisplayFields($displayFields);
        return $this->owner;
    }

    /**
     * @return array
     */
    public function getFieldFormatting()
    {
        return $this->getDataColumns()->getFieldFormatting();
    }

    /**
     * Shorhand for setting field formatting
     *
     * @param array $fieldFormatting
     * @return GridField
     */
    public function setFieldFormatting($fieldFormatting)
    {
        $this->getDataColumns()->setFieldFormatting($fieldFormatting);
        return $this->owner;
    }
}
