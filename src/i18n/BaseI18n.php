<?php

namespace LeKoala\Base\i18n;

use SilverStripe\i18n\i18n;
use SilverStripe\Core\Config\Configurable;
use TractorCow\Fluent\Extension\FluentExtension;
use TractorCow\Fluent\Model\Locale;

/**
 * i18n helper class
 */
class BaseI18n
{
    use Configurable;

    /**
     * The default key for global translation
     */
    const GLOBAL_ENTITY = 'Global';

    /**
     * Provision fluent locales defined in yml
     * Pass /dev/build?provisionLocales=1 to provision locale on dev/build
     *
     * eg:
     * LeKoala\Base\i18n\BaseI18n:
     *   default_locales:
     *     - en_US
     *     - fr_FR
     * @config
     * @var array
     */
    private static $default_locales = [];

    protected static $locale_cache = [];

    /**
     * Get a global translation
     *
     * @param string $entity
     * @return string
     */
    public static function globalTranslation($entity)
    {
        $parts = explode('.', $entity);
        if (count($parts) == 1) {
            array_unshift($parts, self::GLOBAL_ENTITY);
        }
        return i18n::_t(implode('.', $parts), $entity);
    }

    /**
     * Make sure we get a proper two characters lang
     *
     * @param string|object $lang a string or a fluent locale object
     * @return string a two chars lang
     */
    public static function get_lang($lang = null)
    {
        if (!$lang) {
            $lang = i18n::get_locale();
        }
        if (is_object($lang)) {
            $lang = $lang->Locale;
        }
        return substr($lang, 0, 2);
    }

    /**
     * Get a locale from the lang
     *
     * @param string $lang
     * @return string
     */
    public static function get_locale($lang)
    {
        // Use fluent data
        if (class_exists(Locale::class)) {
            if (empty(self::$locale_cache)) {
                $fluentLocales = Locale::getLocales();
                foreach ($fluentLocales as $locale) {
                    self::$locale_cache[self::get_lang($locale->Locale)] = $locale->Locale;
                }
            }
            if (isset(self::$locale_cache[$lang])) {
                return self::$locale_cache[$lang];
            }
        }
        // Guess
        $localesData = i18n::getData();
        return $localesData->localeFromLang($lang);
    }
}
