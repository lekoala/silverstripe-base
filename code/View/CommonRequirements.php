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
}
