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
    public static function getName($code)
    {
        $list = self::get();
        if (isset($list[$code])) {
            return $list[$code];
        }
        return $code;
    }
}
