<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;

/**
 * Useful when rendering lots of dropdown fields on a given page
 * Do not use DropdownField.ss to get more performance
 *
 * SilverStripe\Core\Injector\Injector:
 *   SilverStripe\Forms\DropdownField:
 *     class: LeKoala\Base\Forms\FastDropdownField
 */
class FastDropdownField extends DropdownField
{
    /**
     * @param FieldList $fields
     * @param DropdownField $field
     * @return $this
     */
    public static function replaceField(FieldList $fields, DropdownField $field)
    {
        $newField = $field->castedCopy(FastDropdownField::class);
        $fields->replaceField($field->getName(), $newField);
        return $newField;
    }

    public function Field($properties = [])
    {
        $options = [];

        // Add all options
        foreach ($this->getSourceEmpty() as $value => $title) {
            $selected = $this->isSelectedValue($value, $this->Value());
            if ($selected) {
                $selected = ' selected="selected"';
            }
            $disabled = false;
            if ($this->isDisabledValue($value) && $title != $this->getEmptyString()) {
                $disabled = ' disabled';
            }
            $item = '<option value="' . $value . '"' . $selected . $disabled . '>' . $title . '</option>';
            $options[] = $item;
        }

        $this->extend('onBeforeRender', $this, $properties);

        // Do not render using template engine because it's really slow for Options
        // Added benefit: your html source won't look like a total mess
        $AttributesHTML = $this->getAttributesHTML($properties);
        $OptionsHTML = implode("\n", $options);

        $result = "<select $AttributesHTML>\n$OptionsHTML\n</select>";

        return $result;
    }

    public function Type()
    {
        return "dropdown";
    }
}
