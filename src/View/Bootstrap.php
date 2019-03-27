<?php

namespace LeKoala\Base\View;

use SilverStripe\View\Requirements;
use SilverStripe\Core\Config\Configurable;

/**
 * Helpers for bootstrap 4
 *
 * You can also use in your config.yml
 *
 * Page:
 *   extensions:
 *     - LeKoala\Base\Extensions\BootstrapPageExtension
 */
class Bootstrap
{
    use Configurable;

    /**
     * @config
     * @var boolean
     */
    private static $enabled = true;

    /**
     * @config
     * @var string
     */
    private static $jquery_version = '3.3.1';

    /**
     * @config
     * We use 4.3.0 and not 4.3.1 that has issues with IE11
     * @var string
     */
    private static $bootstrap_version = '4.3.0';

    /**
     * @config
     * @var string
     */
    private static $bootstrap_native_version = '2.0.25';

    /**
     * @config
     * @var string
     */
    private static $js_cookie_version = '2.2.0';

    /**
     * Require defaults js requirements for bootstrap
     *
     * @return void
     */
    public static function defaultRequirements()
    {
        $jquery_version = self::config()->jquery_version;
        $bootstrap_version = self::config()->bootstrap_version;
        $js_cookie_version = self::config()->js_cookie_version;

        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/jquery/$jquery_version/jquery.min.js");
        // with Popper JS but no jQuery
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/$bootstrap_version/js/bootstrap.bundle.min.js");
        // Helpers
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/js-cookie/$js_cookie_version/js.cookie.min.js");
        Requirements::javascript("base/javascript/BootstrapHelpers.js");
    }

    /**
     * @return boolean
     */
    public static function enabled()
    {
        return self::config()->enabled;
    }

    /**
     *
     * @link https://github.com/thednp/bootstrap.native
     * @return void
     */
    public static function nativeRequirements()
    {
        $version = self::config()->bootstrap_native_version;

        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/bootstrap.native/$version/bootstrap-native-v4.min.js");
    }
}
