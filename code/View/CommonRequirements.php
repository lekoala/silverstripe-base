<?php

namespace LeKoala\Base\View;

use SilverStripe\i18n\i18n;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\Core\Config\Configurable;

class CommonRequirements
{
    use Configurable;

    /**
     * @config
     * @var string
     */
    private static $accouting_version = '0.4.1';

    /**
     * @config
     * @var string
     */
    private static $moment_version = '2.22.2';

    /**
     * @config
     * @var string
     */
    private static $moment_timezone_version = '0.5.20';

    /**
     * @config
     * @var string
     */
    private static $datefns_version = '1.29.0';

    /**
     * @config
     * @var string
     */
    private static $countdown_version = '2.2.0';

    /**
     * @config
     * @var string
     */
    private static $fa4_version = '4.7.0';

    /**
     * @config
     * @var string
     */
    private static $fa5_version = '5.1.0';

    /**
     * @config
     * @var string
     */
    private static $boxicons_version = '1.6.0';

    /**
     * @config
     * @var string
     */
    private static $plyr_version = '3.3.22';

    /**
     * Include all files in a given path
     *
     * @param string $path
     * @return void
     */
    public static function includeInPath($path)
    {
        $js = glob($path . '/*.js');
        $base = Director::baseFolder();
        foreach ($js as $file) {
            $file = str_replace($base . '/', '', $file);
            Requirements::javascript($file);
        }
    }

    /**
     * @link https://polyfill.io/v2/docs/
     * @return void
     */
    public static function polyfillIo()
    {
        Requirements::javascript('https://cdn.polyfill.io/v2/polyfill.min.js');
    }

    /**
     * @link https://github.com/sampotts/plyr
     * @param bool $css Include css, defaults to true
     * @param bool $polyfilled Use polyfilled version, defaults to false
     * @return void
     */
    public static function plyr($css = true, $polyfilled = false)
    {
        $version = self::config()->plyr_version;
        if ($css) {
            Requirements::css("https://cdn.plyr.io/$version/plyr.css");
        }
        if ($polyfilled) {
            Requirements::javascript("https://cdn.plyr.io/$version/plyr.polyfilled.js");
        } else {
            Requirements::javascript("https://cdn.plyr.io/$version/plyr.js");
        }

    }

    /**
     * @link https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment-with-locales.min.js
     * @param string $lang
     * @param boolean $timezone
     * @return void
     */
    public static function moment($lang = null, $timezone = false)
    {
        if ($lang === null) {
            $lang = substr(i18n::get_locale(), 0, 2);
        }
        $version = self::config()->moment_version;
        $tzversion = self::config()->moment_timezone_version;
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/moment.js/$version/moment-with-locales.min.js");
        if ($lang != 'en') {
            Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/moment.js/$version/locale/$lang.js");
        }
        if ($timezone) {
            Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/$tzversion/moment-timezone-with-data.min.js");
        }
    }

    /**
     * @link http://openexchangerates.github.io/accounting.js/
     * @return void
     */
    public static function accounting()
    {
        $version = self::config()->accouting_version;
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/accounting.js/$version/accounting.min.js");
    }

    /**
     * @link https://date-fns.org/
     * @return void
     */
    public static function datefns()
    {
        $version = self::config()->datefns_version;
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/date-fns/$version/date_fns.min.js");
    }

    /**
     * @link http://hilios.github.io/jQuery.countdown/
     * @return void
     */
    public static function countdown()
    {
        $version = self::config()->countdown_version;
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/jquery.countdown/$version/jquery.countdown.min.js");
    }

    /**
     * @link https://fontawesome.com/v4.7.0/cheatsheet/
     * @return void
     */
    public static function fontAwesome4()
    {
        $version = self::config()->fa4_version;
        Requirements::css("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/$version/css/font-awesome.min.css");
    }

    /**
     * @link https://fontawesome.com/cheatsheet
     * @return void
     */
    public static function fontAwesome5()
    {
        $version = self::config()->fa5_version;
        Requirements::css("https://use.fontawesome.com/releases/v${version}/css/all.css");
    }

    /**
     * @link https://boxicons.com/cheatsheet
     * @return void
     */
    public static function boxIcons()
    {
        $version = self::config()->boxicons_version;
        Requirements::css("https://cdn.jsdelivr.net/npm/boxicons@$version/css/boxicons.min.css");
    }
}
