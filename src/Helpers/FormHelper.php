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
        if (strpos($str, '[') === 0) {
            return json_decode($str, true);
        }
        return explode(',', $str);
    }
}
