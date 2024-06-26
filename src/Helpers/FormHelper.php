<?php

namespace LeKoala\Base\Helpers;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\CommonExtensions\SortableExtension;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

/**
 * Helpers for forms
 */
class FormHelper
{
    /**
     * Decode or explode a given string
     *
     * @param string $str
     * @return array<mixed>
     */
    public static function decodeOrExplode($str)
    {
        if (empty($str)) {
            return [];
        }
        if (strpos($str, '[') === 0) {
            return json_decode($str, true);
        }
        return explode(',', $str);
    }

    /**
     * convert camel case to words
     *
     * @param string $str
     * @return string
     */
    public static function niceLabel($str)
    {
        $parts = preg_split('/(?=[A-Z])/', $str);
        return implode(' ', $parts);
    }

    /**
     * Helps dealing with browser autofill
     *
     * @param FormField $field
     * @return void
     */
    public static function disableAutofill(FormField &$field)
    {
        $field->setAttribute("readonly", "readonly");
        $field->addExtraClass("autofill-disabled");
        $field->setAttribute("onfocus", "this.removeAttribute('readonly')");
    }

    /**
     * Pass an array of names or an array of name => flag for dynamic conditions
     * @param FieldList $fields
     * @param string|array $names
     * @return void
     */
    public static function makeFieldReadonly(FieldList $fields, $names)
    {
        if (!is_array($names)) {
            $names = [$names];
        }

        foreach ($names as $idx => $item) {
            if (is_numeric($idx)) {
                $fieldName = ($item instanceof FormField) ? $item->getName() : $item;
                $srcField = $fields->dataFieldByName($fieldName);
                if ($srcField) {
                    $fields->replaceField($fieldName, $srcField->performReadonlyTransformation());
                }
            } elseif ($item) {
                $srcField = $fields->dataFieldByName($idx);
                if ($srcField) {
                    $fields->replaceField($idx, $srcField->performReadonlyTransformation());
                }
            }
        }
    }

    /**
     * @param DataObject $dataobject
     * @param string $field
     * @return GridField
     */
    public static function getGridField(DataObject $dataobject, $field)
    {
        $config = GridFieldConfig_RecordEditor::create();
        /* @var $list DataList */
        $list =  $dataobject->$field();
        $singl = singleton($list->dataClass());
        if ($singl->hasExtension(SortableExtension::class)) {
            $config->addComponent(new GridFieldOrderableRows());
        }
        $gridfield = new GridField($field, $dataobject->fieldLabel($field), $list, $config);
        return $gridfield;
    }

    /**
     * Group fields
     *
     * Usage:
     * FormHelper::groupFields($fields, 'StartDate', 'EndDate');
     *
     * @param FieldList $fields
     * @param string $field1
     * @param string $field2
     * @param string $field3
     * @param string $field4
     * @return FieldGroup
     */
    public static function groupFields(FieldList $fields, $field1, $field2, $field3 = null, $field4 = null)
    {
        $f1 = $f2 = $f3 = $f4 = null;
        $f1 = $fields->dataFieldByName($field1);
        $f2 = $fields->dataFieldByName($field2);
        if ($field3) {
            $f3 = $fields->dataFieldByName($field3);
        }
        if ($field4) {
            $f4 = $fields->dataFieldByName($field4);
        }
        $g = new FieldGroup();
        // Insert group before the first field
        $fields->insertBefore($field1, $g);

        // Move fields inside the group
        $fields->remove($f1);
        $g->push($f1);
        $fields->remove($f2);
        $g->push($f2);

        // Optional fields
        if ($field3) {
            $f3 = $fields->dataFieldByName($field3);
            $fields->remove($f3);
            $g->push($f3);
        }
        if ($field4) {
            $f4 = $fields->dataFieldByName($field4);
            $fields->remove($f4);
            $g->push($f4);
        }

        return $g;
    }
}
