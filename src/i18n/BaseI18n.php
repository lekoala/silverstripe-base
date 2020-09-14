<?php

namespace LeKoala\Base\i18n;

use SilverStripe\i18n\i18n;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;
use SilverStripe\Core\Config\Configurable;
use TractorCow\Fluent\Extension\FluentExtension;

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
            $lang = self::get_locale();
        }
        if (is_object($lang)) {
            $lang = $lang->Locale;
        }
        return substr($lang, 0, 2);
    }

    /**
     * Get the right locale (using fluent data if exists)
     *
     * @return string
     */
    public static function get_locale()
    {
        if (class_exists(FluentState::class)) {
            return FluentState::singleton()->getLocale();
        }
        return i18n::get_locale();
    }

    /**
     * Get a locale from the lang
     *
     * @param string $lang
     * @return string
     */
    public static function get_locale_from_lang($lang)
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

    /**
     * Do we have the subsite module installed
     * TODO: check if it might be better to use module manifest instead?
     *
     * @return bool
     */
    public static function usesFluent()
    {
        return class_exists(FluentState::class);
    }

    /**
     * Execute the callback in given subsite
     *
     * @param string $locale
     * @param callable $cb
     * @return mixed the callback result
     */
    public static function withLocale($locale, $cb)
    {
        if (!self::usesFluent() || !$locale) {
            $cb();
            return;
        }
        if (!is_string($locale)) {
            $locale = $locale->Locale;
        }
        $state = FluentState::singleton();
        return $state->withState(function ($state) use ($locale, $cb) {
            $state->setLocale($locale);
            return $cb();
        });
    }

    /**
     * Execute the callback for all locales
     *
     * @param callable $cb
     * @return array an array of callback results
     */
    public static function withLocales($cb)
    {
        if (!self::usesFluent()) {
            $cb();
            return [];
        }
        $allLocales = Locale::get();
        $results = [];
        foreach ($allLocales as $locale) {
            $results[] = self::withLocale($locale, $cb);
        }
        return $results;
    }
}
