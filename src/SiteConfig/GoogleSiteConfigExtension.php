<?php
namespace LeKoala\Base\SiteConfig;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;
use LeKoala\Base\View\CookieConsent;
use LeKoala\Base\Forms\Bootstrap\Tab;

/**
 * Google SiteConfig stuff
 *
 * @property string $GoogleAnalyticsCode
 * @property string $GoogleMapsApiKey
 */
class GoogleSiteConfigExtension extends DataExtension
{
    private static $db = [
        "GoogleAnalyticsCode" => "Varchar(59)", // UA-XXXXXXX-Y
        "GoogleMapsApiKey" => "Varchar(59)",
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $tab = $fields->fieldByName(SiteConfigExtension::EXTERNAL_SERVICES_TAB);
        if (!$tab) {
            $tab = new Tab(SiteConfigExtension::EXTERNAL_SERVICES_TAB);
            $fields->addFieldToTab('Root', $tab);
        }
        $GoogleAnalyticsCode = new TextField('GoogleAnalyticsCode');
        $tab->push($GoogleAnalyticsCode);
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
     * Called automatically by BaseContentController
     * @return void
     */
    public function requireGoogleAnalytics()
    {
        if (!Director::isLive()) {
            return false;
        }
        if (!$this->owner->GoogleAnalyticsCode) {
            return false;
        }
        $script = <<<JS
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
ga('create', '{$this->owner->GoogleAnalyticsCode}', 'auto');
ga('send', 'pageview');
JS;
        if (CookieConsent::IsEnabled()) {
            CookieConsent::addScript($script, "GoogleAnalytics");
        } else {
            Requirements::customScript($script, "GoogleAnalytics");
        }

        return true;
    }
}
