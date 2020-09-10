<?php

namespace LeKoala\Base\View;

use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Control\Cookie;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Privacy\PrivacyNoticePage;
use LeKoala\Base\Privacy\CookiesRequiredPage;
use SilverStripe\Control\Director;

/**
 * Add cookie consent to your website
 *
 * When consent is given, global onConsentReceived() will be called. As an helper, you can use CookieConsent::addScript to do that for you.
 *
 * For performance, remember to use this config and include the relevant sass file in base/sass/vendor
 *
 *   LeKoala\Base\View\CookieConsent:
 *     inline_css: true
 *
 * @link https://www.osano.com/cookieconsent
 * @link https://www.osano.com/cookieconsent/documentation/disabling-cookies/
 * @link https://cookiesandyou.com/
 * @link https://www.cookiebot.com/en/gdpr-cookies/
 */
class CookieConsent
{
    use Configurable;

    const STATUS_ALLOW = 'allow';
    const STATUS_DENY = 'deny';
    const STATUS_DISMISS = 'dismiss';

    /**
     * @config
     * @var string
     */
    private static $version = '3.1.0';

    /**
     * @config
     * @var boolean
     */
    private static $enabled = true;

    /**
     * @config
     * @var boolean
     */
    private static $cookies_required = false;

    /**
     * @config
     * @var boolean
     */
    private static $inline_css = false;

    /**
     * @config
     * @var string
     */
    private static $opts = [
        'position' => 'bottom-left',
        'theme' => 'classic', // leave empty or classic or edgeless
        'type' => 'opt-in',
    ];

    /**
     * @var array
     */
    protected static $scripts = [];

    /**
     * @var string
     */
    protected static $customMessage;

    /**
     * Add requirements
     *
     * Make sure to call this AFTER you have define scripts that should be loaded conditionally
     * @link https://stackoverflow.com/questions/45794634/loading-google-analytics-after-page-load-by-appending-script-in-head-doesnt-alw
     * @return void
     */
    public static function requirements()
    {
        $SiteConfig = SiteConfig::current_site_config();

        $conf = self::config();

        // options to pass to js constructor
        $opts = $conf->opts;

        $use_theme = $conf->force_colors ? false : true;

        $privacyLink = 'https://cookiesandyou.com/';
        // If we have a privacy notice, use it!
        $privacyNotice = DataObject::get_one(PrivacyNoticePage::class);
        if ($privacyNotice) {
            $privacyLink = $privacyNotice->Link();
        }

        $message = _t('CookieConsent.MESSAGE', "This website uses cookies to ensure you get the best experience on your website");
        if (self::config()->cookies_required) {
            $message = _t('CookieConsent.MESSAGE_REQUIRED', "This website require the usage of cookies. Please accept them to continue");
        }
        if (self::$customMessage) {
            $message = self::$customMessage;
        }

        $PrimaryColor = $SiteConfig->dbObject('PrimaryColor');
        $ThemeColor = $SiteConfig->dbObject('ThemeColor');

        /*
        Sample config:

        LeKoala\Base\View\CookieConsent:
            popup_background: '#232323'
            popup_text: '#fafafa'
            button_background: '#98252d'
            button_text: '#ffffff'
            force_colors: 1
        */
        $paletteOpts = [
            'palette' => [
                'popup' => [
                    'background' => $conf->popup_background ? $conf->popup_background : '#efefef',
                    'text' => $conf->popup_text ? $conf->popup_text : '#404040',
                ],
                'button' => [
                    'background' =>  $conf->button_background ? $conf->button_background : '#8ec760',
                    'text' => $conf->button_text ? $conf->button_text : '#ffffff',
                ]
            ]
        ];
        if ($PrimaryColor->getValue() && $use_theme) {
            $paletteOpts['palette']['button'] = [
                'background' => $PrimaryColor->HighlightColor(),
                'text' => $PrimaryColor->HighlightContrastColor(),
            ];
        }
        if ($ThemeColor->getValue() && $use_theme) {
            $paletteOpts['palette']['popup'] = [
                'background' => $ThemeColor->Color(),
                'text' => $ThemeColor->ContrastColor(),
            ];
        }
        $contentOpts = [
            'content' => [
                'message' => $message,
                'deny' => _t('CookieConsent.DECLINE', 'Decline'),
                'allow' => _t('CookieConsent.ALLOWCOOKIES', 'Allow cookies'),
                'link' => _t('CookieConsent.LINK', 'Learn more'),
                'href' => $privacyLink,
            ]
        ];
        $baseOpts = array_merge($paletteOpts, $contentOpts);
        $finalOpts = array_merge($baseOpts, $opts);
        $jsonOpts = json_encode($finalOpts);

        // Include script
        $version = self::config()->version;
        if (!self::config()->inline_css) {
            Requirements::css("//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/$version/cookieconsent.min.css");
        }
        Requirements::javascript("//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/$version/cookieconsent.min.js");

        // Create url to redirect to if cookies are dismissed
        $cookiesRequired = self::config()->cookies_required ? 'true' : 'false';
        $cookiesLink = '/';
        if (self::config()->cookies_required) {
            $page = DataObject::get_one(CookiesRequiredPage::class);
            if ($page) {
                $cookiesLink = '/' . $page->Link();
            }
        }

        $js = '';
        if (!empty(self::$scripts)) {
            $js .= 'function onConsentReceived() {';
            foreach (self::$scripts as $name => $script) {
                $js .= "\n//$name\n$script";
            }
            $js .= "\n}\n";
        }

        // Include custom init
        $js .= <<<JS
window.addEventListener("load", function(){
    var opts = $jsonOpts;
    var onChange = function(status) {
        // If we required cookies, redirect to the page
        if(status == 'dismiss' && $cookiesRequired) {
            window.location.href = '$cookiesLink';
        }
        // Call any third party script
        if(status == "allow" && typeof onConsentReceived != 'undefined') {
            onConsentReceived();
        }
    };
    var onInit = function(status) {
        // Call any third party script
        if(status == "allow" && typeof onConsentReceived != 'undefined') {
            onConsentReceived();
        }
    };
    opts.onInitialise = onInit;
    opts.onStatusChange = opts.onRevokeChoice = onChange;
    window.cookieconsent.initialise(opts);
});
JS;
        Requirements::customScript($js, 'CookiesConsentInit');
    }

    /**
     * @return array
     */
    public static function getScripts()
    {
        return self::$scripts;
    }

    /**
     * @return void
     */
    public static function clearScripts()
    {
        self::$scripts = [];
    }

    /**
     * Add a script that should be wrapped by onConsentReceived
     *
     * @param string $script
     * @param string $name
     * @return void
     */
    public static function addScript($script, $name)
    {
        self::$scripts[$name] = $script;
    }

    /**
     * Catches everyting from the first ( to the final ;
     * @param string $path The path to the file like app/templates/MyScript.ss
     * @return void
     */
    public static function addScriptFromTemplate($path)
    {
        $name = pathinfo($path, PATHINFO_FILENAME);
        $filename = Director::baseFolder() . '/' . trim($path, '/');
        $script = file_get_contents($filename);
        $start = strpos($script, '(');
        $end = strrpos($script, ';');
        $length = $end - $start + 1;
        $script = substr($script, $start, $length);
        self::addScript($script, $name);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function setCustomMessage($message)
    {
        self::$customMessage = $message;
    }

    /**
     * Clear requirements, useful if you don't want any popup on a specific page after init
     *
     * @return void
     */
    public static function clearRequirements()
    {
        $version = self::config()->version;
        Requirements::clear("//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/$version/cookieconsent.min.css");
        Requirements::clear("//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/$version/cookieconsent.min.js");
        Requirements::clear('CookiesConsentInit');
    }

    /**
     * @return boolean
     */
    public static function IsEnabled()
    {
        return self::config()->enabled;
    }

    /**
     * Get the current status
     *
     * @return string deny, allow or dismiss
     */
    public static function Status()
    {
        return Cookie::get('cookieconsent_status');
    }

    /**
     * @return bool
     */
    public static function IsAllowed()
    {
        return self::Status() == self::STATUS_ALLOW;
    }

    /**
     * Helper method to set cookies if accepted
     *
     * @param string $name
     * @param string $value
     * @param integer $expiry
     * @return void
     */
    public static function setCookie($name, $value, $expiry = 90)
    {
        if (self::IsAllowed()) {
            $httpOnly = true;
            $secure = Director::is_https();
            $path = $domain = null;
            return Cookie::set($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
        }
        return false;
    }

    /**
     * Force the status to a specific value
     *
     * @param string $status see const STATUS_**** for possible values
     * @return void
     */
    public static function forceStatus($status)
    {
        return Cookie::set('cookieconsent_status', $status, 90, null, null, false, false);
    }

    /**
     * @return void
     */
    public static function clearStatus()
    {
        return Cookie::force_expiry('cookieconsent_status');
    }

    /**
     * @return void
     */
    public static function forceAllow()
    {
        return self::forceStatus(self::STATUS_ALLOW);
    }

    /**
     * @return void
     */
    public static function forceDismiss()
    {
        return self::forceStatus(self::STATUS_DISMISS);
    }

    /**
     * @return void
     */
    public static function forceDeny()
    {
        return self::forceStatus(self::STATUS_ALLOW);
    }
}
