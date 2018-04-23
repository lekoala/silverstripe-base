<?php

namespace LeKoala\Base\View;

use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Configurable;

/**
 *
 * @link https://cookieconsent.insites.com
 * @link https://cookieconsent.insites.com/documentation/disabling-cookies/
 * @link https://cookiesandyou.com/
 * @link https://www.cookiebot.com/en/gdpr-cookies/
 *
 */
class CookiesConsent
{
    use Configurable;

        /**
     * @var string
     */
    private static $version = '3.0.3';

    /**
     * @var string
     */
    private static $opts = [
        'position' => 'bottom',
        'theme' => 'edgeless',
        'type' => 'opt-in',
    ];


    /**
     * Add AlertifyJS requirements
     */
    public static function requirements()
    {
        $SiteConfig = SiteConfig::current_site_config();
        $opts = self::config()->opts;
        $baseOpts = [
            'palette' => [
                'popup' => [
                    'background' => $SiteConfig->ThemeColor,
                    'text' =>  $SiteConfig->dbObject('ThemeColor')->ContrastColor(),
                ],
                'button' => [
                    'background' => $SiteConfig->PrimaryColor,
                    'text' => $SiteConfig->dbObject('PrimaryColor')->ContrastColor(),
                ]
            ],
            'content' => [
                'message' => _t('CookieConsent.MESSAGE', "This website uses cookies to ensure you get the best experience on your website"),
                'dismiss' => _t('CookieConsent.DECLINE', 'Decline'),
                'allow' => _t('CookieConsent.ALLOWCOOKIES', 'Allow cookies'),
                'link' => _t('CookieConsent.LINK', 'Learn more'),
                'href' => 'https://cookiesandyou.com/'
            ]
        ];
        $finalOpts = array_merge($baseOpts, $opts);
        $jsonOpts = json_encode($finalOpts, JSON_PRETTY_PRINT);
        //TODO: append hooks for disabling/enabling cookies
        //@link https://cookieconsent.insites.com/documentation/disabling-cookies/

        // Include script
        $version = self::config()->version;
        Requirements::css("//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/$version/cookieconsent.min.css");
        Requirements::javascript("//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/$version/cookieconsent.min.js", ['defer' => true]);

        // Include custom init
        $js = <<<JS
window.addEventListener("load", function(){
    window.cookieconsent.initialise($jsonOpts)
});
JS;
        Requirements::customScript($js,'CookiesConsentInit');
    }

}
