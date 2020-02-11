<?php

namespace LeKoala\Base\Helpers;

use LeKoala\Base\Extensions\SortableExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Helpers for forms
 */
class FormHelper
{
    /**
     * Decode or explode a given string
     *
     * @param string $str
     * @return array
     */
    public static function decodeOrExplode($str)
    {
        if (empty($str)) {
            return [];
        }
        if (strpos($str, '[') === 0) {
            return json_decode($str, JSON_OBJECT_AS_ARRAY);
        }
        return explode(',', $str);
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
}
