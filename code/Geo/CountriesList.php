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
     * @return array
     */
    public static function get()
    {
        $intl = new IntlLocales;
        return $intl->getCountries();
    }
}
