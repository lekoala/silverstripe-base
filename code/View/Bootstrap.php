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
     * @var string
     */
    private static $jquery_version = '3.3.1';

    /**
     * @var string
     */
    private static $bootstrap_version = '4.1.0';

    /**
     * @var string
     */
    private static $bootstrap_native_version = '2.0.15';

    /**
     * Require defaults js requirements for bootstrap
     *
     * @return void
     */
    public static function defaultRequirements()
    {
        $jquery_version = self::config()->jquery_version;
        $bootstrap_version = self::config()->bootstrap_version;

        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/jquery/$jquery_version/jquery.min.js", ['defer' => true]);
        // with Popper JS but no jQuery
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/$bootstrap_version/js/bootstrap.bundle.js", ['defer' => true]);
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.2.0/js.cookie.min.js", ['defer' => true]);
        Requirements::javascript("base/javascript/BootstrapHelpers.js", ['defer' => true]);
    }

    /**
     *
     * @link https://github.com/thednp/bootstrap.native
     * @return void
     */
    public static function nativeRequirements()
    {
        $version = self::config()->bootstrap_native_version;

        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/bootstrap.native/$version/bootstrap-native-v4.min.js", ['defer' => true]);
    }

}
