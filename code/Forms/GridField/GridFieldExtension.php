<?php
namespace LeKoala\Base\Forms\GridField;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

/**
 * Class \LeKoala\Base\Forms\GridField\GridFieldExtension
 *
 * @property \SilverStripe\Forms\GridField\GridField|\LeKoala\Base\Forms\GridField\GridFieldExtension $owner
 */
class GridFieldExtension extends Extension
{
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

        foreach ($fields as $index => $v) {
            if (is_numeric($index)) {
                $arr[$v] = $singl->fieldLabel($v);
            } else {
                $arr[$index] = $v;
            }
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
        return $this->getDataColumns()->getDisplayFields();
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
