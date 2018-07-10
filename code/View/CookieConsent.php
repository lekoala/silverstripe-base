<?php

namespace LeKoala\Base\View;

use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Control\Cookie;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Privacy\PrivacyNoticePage;

/**
 *
 * @link https://cookieconsent.insites.com
 * @link https://cookieconsent.insites.com/documentation/disabling-cookies/
 * @link https://cookiesandyou.com/
 * @link https://www.cookiebot.com/en/gdpr-cookies/
 *
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
    private static $version = '3.0.6';

    /**
     * @config
     * @var boolean
     */
    private static $cookies_required = false;

    /**
     * @config
     * @var string
     */
    private static $opts = [
        'position' => 'bottom',
        'theme' => 'edgeless',
        'type' => 'opt-in',
    ];


    /**
     * Add requirements
     */
    public static function requirements()
    {
        $SiteConfig = SiteConfig::current_site_config();
        $opts = self::config()->opts;

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

        $PrimaryColor = $SiteConfig->dbObject('PrimaryColor');
        $ThemeColor = $SiteConfig->dbObject('ThemeColor');

        $paletteOpts = [
            'palette' => [
                'popup' => [
                    'background' => '#efefef',
                    'text' => '#404040',
                ],
                'button' => [
                    'background' => '#8ec760',
                    'text' => '#ffffff',
                ]
            ]
        ];
        if ($PrimaryColor->getValue()) {
            $paletteOpts = [
                'palette' => [
                    'popup' => [
                        'background' => $ThemeColor->Color(),
                        'text' => $ThemeColor->ContrastColor(),
                    ],
                    'button' => [
                        'background' => $PrimaryColor->HighlightColor(),
                        'text' => $PrimaryColor->HighlightContrastColor(),
                    ]
                ]
            ];
        }
        $contentOpts = [
            'content' => [
                'message' => $message,
                'dismiss' => _t('CookieConsent.DECLINE', 'Decline'),
                'allow' => _t('CookieConsent.ALLOWCOOKIES', 'Allow cookies'),
                'link' => _t('CookieConsent.LINK', 'Learn more'),
                'href' => $privacyLink,
            ]
        ];
        $baseOpts = array_merge($paletteOpts, $contentOpts);
        $finalOpts = array_merge($baseOpts, $opts);
        $jsonOpts = json_encode($finalOpts, JSON_PRETTY_PRINT);

        // Include script
        $version = self::config()->version;
        Requirements::css("//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/$version/cookieconsent.min.css");
        Requirements::javascript("//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/$version/cookieconsent.min.js");

        // Create url to redirect to if cookies are dismissed
        $cookiesRequired = self::config()->cookies_required ? 'true' : 'false';
        $cookiesLink = '/';
        if (self::config()->cookies_required) {
            //TODO: make url configurable
            $cookiesLink ='/cookies-required';
        }

        // Include custom init
        $js = <<<JS
window.addEventListener("load", function(){
    var opts = $jsonOpts;
    var onChange = function(status) {
        // If we required cookies, redirect to the page
        if(status == 'dismiss' && $cookiesRequired) {
            window.location.href = '$cookiesLink';
        }
    };
    opts.onInitialise = opts.onStatusChange = opts.onRevokeChoice = onChange;
    window.cookieconsent.initialise(opts);
});
JS;
        Requirements::customScript($js, 'CookiesConsentInit');
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
     * Get the current status
     *
     * @return string deny, allow or dismiss
     */
    public static function Status()
    {
        return Cookie::get('cookieconsent_status');
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
