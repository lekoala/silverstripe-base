<?php

namespace LeKoala\Base\Helpers;

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
}
