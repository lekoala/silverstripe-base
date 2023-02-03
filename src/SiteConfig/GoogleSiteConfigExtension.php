<?php

namespace LeKoala\Base\SiteConfig;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;
use LeKoala\Base\View\CookieConsent;
use LeKoala\Base\Forms\Bootstrap\Tab;
use SilverStripe\Forms\CheckboxField;
use LeKoala\Base\View\CommonRequirements;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Google SiteConfig stuff
 *
 * SilverStripe\SiteConfig\SiteConfig:
 *   extensions:
 *     - LeKoala\Base\SiteConfig\GoogleSiteConfigExtension
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\LeKoala\Base\SiteConfig\GoogleSiteConfigExtension $owner
 * @property string $GoogleAnalyticsCode
 * @property boolean $GoogleAnalyticsWithoutCookies
 * @property string $GoogleMapsApiKey
 */
class GoogleSiteConfigExtension extends DataExtension
{
    private static $db = [
        "GoogleAnalyticsCode" => "Varchar(59)", // GA_MEASUREMENT_ID : UA-XXXXXXX-Y
        "GoogleAnalyticsWithoutCookies" => "Boolean",
        "GoogleMapsApiKey" => "Varchar(59)",
    ];

    protected static $conversions = [];

    public function updateCMSFields(FieldList $fields)
    {
        $tab = $fields->fieldByName(SiteConfigExtension::EXTERNAL_SERVICES_TAB);
        if (!$tab) {
            $tab = new Tab(SiteConfigExtension::EXTERNAL_SERVICES_TAB);
            $fields->addFieldToTab('Root', $tab);
        }
        $GoogleAnalyticsCode = new TextField('GoogleAnalyticsCode');

        $gtag = $this->EnableGtagManager();
        if ($gtag) {
            $GoogleAnalyticsCode->setAttribute("placeholder", "G-XXXXXXXXXX");
        } else {
            $GoogleAnalyticsCode->setAttribute("placeholder", "UA-XXXXXXX-Y");
        }

        $tab->push($GoogleAnalyticsCode);
        if ($gtag) {
            $fields->removeByName('GoogleAnalyticsWithoutCookies');
        } else {
            $GoogleAnalyticsWithoutCookies = new CheckboxField('GoogleAnalyticsWithoutCookies');
            $tab->push($GoogleAnalyticsWithoutCookies);
        }

        $GoogleMapsApiKey = new TextField('GoogleMapsApiKey');
        $tab->push($GoogleMapsApiKey);
    }


    /**
     * Call this in your controller manually
     * @return void
     */
    public function requireGoogleMaps()
    {
        if (!$this->owner->GoogleMapsApiKey) {
            return false;
        }
        Requirements::javascript('https://maps.googleapis.com/maps/api/js?key=' . $this->owner->GoogleMapsApiKey);
        return true;
    }

    /**
     * @return bool
     */
    public function shouldRequireGoogleAnalytics()
    {
        if (!Director::isLive()) {
            return false;
        }
        if (!$this->owner->GoogleAnalyticsCode) {
            return false;
        }
        return true;
    }

    public function EnableGtagManager()
    {
        $gtag = SiteConfig::config()->gtag_manager; // required for GA 4
        $code = $this->owner->GoogleAnalyticsCode ?? '';
        // autodetect gtag
        if (strpos($code, 'G-') === 0) {
            $gtag = true;
        }
        if (strpos($code, 'UA-') === 0) {
            $gtag = false;
        }

        return $gtag;
    }

    /**
     * Called automatically by BaseContentController
     * @return bool
     */
    public function requireGoogleAnalytics()
    {
        if (!$this->shouldRequireGoogleAnalytics()) {
            return false;
        }

        $config = SiteConfig::config();
        $gtag =  $this->EnableGtagManager();

        if ($this->owner->GoogleAnalyticsWithoutCookies) {
            CommonRequirements::fingerprintjs();
            $script = <<<JS
FingerprintJS.load().then(function (fp) {
  fp.get().then(function (result) {
    var visitorId = result.visitorId;
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
    ga('create', '{$this->owner->GoogleAnalyticsCode}', {
        'storage': 'none',
        'clientId': visitorId
    });
    ga('set', 'anonymizeIp', true);
    ga('send', 'pageview');
  });
});
JS;
        } else {
            if ($gtag) {
                Requirements::javascript('https://www.googletagmanager.com/gtag/js?id=' . $this->owner->GoogleAnalyticsCode, ['async' => true]);
                $script = <<<JS
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{$this->owner->GoogleAnalyticsCode}');
JS;
            } else {
                $script = <<<JS
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
ga('create', '{$this->owner->GoogleAnalyticsCode}', 'auto');
ga('send', 'pageview');
JS;
            }
        }

        if (!empty(self::$conversions)) {
            foreach (self::$conversions as $conversion) {
                $sendTo = $conversion['send_to'];
                $transactionId = $conversion['transaction_id'];
                $script .= <<<JS
gtag('event', 'conversion', {
    'send_to': '{$sendTo}',
    'transaction_id': '{$transactionId}'
});
JS;
            }
        }

        $conditionalAnalytics = $config->conditional_analytics;

        $uniquenessID = 'ga-tracking';
        // If we don't use cookie, no need to advertise them
        if ($this->owner->GoogleAnalyticsWithoutCookies) {
            $uniquenessID = 'ga';
        }
        // If we use cookies and require cookie consent
        if (CookieConsent::IsEnabled() && !$this->owner->GoogleAnalyticsWithoutCookies && $conditionalAnalytics) {
            CookieConsent::addScript($script, $uniquenessID);
        } else {
            Requirements::customScript($script, $uniquenessID);
        }

        return true;
    }

    /**
     * Track conversion
     *
     * @param string $sendTo
     * @param string $transactionId
     * @return void
     */
    public static function addGoogleAnalyticsConversion($sendTo, $transactionId = '')
    {
        self::$conversions[] = [
            'send_to' => $sendTo,
            'transaction_id' => $transactionId,
        ];
    }
}
