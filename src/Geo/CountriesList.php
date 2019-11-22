<?php

namespace LeKoala\Base\Geo;

use SilverStripe\i18n\Data\Intl\IntlLocales;

/**
 * @author Koala
 */
class CountriesList
{
    /**
     * Get the country list, using IntlLocales
     *
     * Keys are set to uppercase to match ISO standards
     *
     * @return array
     */
    public static function get()
    {
        $intl = new IntlLocales;
        $countries = $intl->getCountries();
        $countries = array_change_key_case($countries, CASE_UPPER);
        return $countries;
    }

    /**
     * @param string $code
     * @return string
     */
    public static function getNameFromCode($code)
    {
        $list = self::get();
        if (isset($list[$code])) {
            return $list[$code];
        }
        return $code;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getCodeFromName($name)
    {
        $list = array_flip(self::get());
        if (isset($list[$name])) {
            return $list[$name];
        }
        return strtoupper(substr($name, 0, 2));
    }
}
