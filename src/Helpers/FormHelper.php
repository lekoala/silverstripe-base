<?php

namespace LeKoala\Base\Helpers;

use SilverStripe\Forms\FormField;

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
}
