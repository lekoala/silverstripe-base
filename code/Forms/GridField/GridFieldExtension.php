<?php
namespace LeKoala\Base\Forms\GridField;

use SilverStripe\Core\Extension;

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
}
