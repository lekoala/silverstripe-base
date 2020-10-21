<?php

namespace LeKoala\Base\SiteConfig;

use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;
use LeKoala\Base\View\CookieConsent;
use LeKoala\Base\Forms\Bootstrap\Tab;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\View\CommonRequirements;

/**
 * Facebook SiteConfig stuff
 *
 * SilverStripe\SiteConfig\SiteConfig:
 *   extensions:
 *     - LeKoala\Base\SiteConfig\FacebookSiteConfigExtension
 *
 * @property \LeKoala\Base\SiteConfig\FacebookSiteConfigExtension $owner
 * @property string $FacebookPixelId
 */
class FacebookSiteConfigExtension extends DataExtension
{
    private static $db = [
        "FacebookPixelId" => "Varchar(59)",
    ];

    protected static $events = [];

    public function updateCMSFields(FieldList $fields)
    {
        $tab = $fields->fieldByName(SiteConfigExtension::EXTERNAL_SERVICES_TAB);
        if (!$tab) {
            $tab = new Tab(SiteConfigExtension::EXTERNAL_SERVICES_TAB);
            $fields->addFieldToTab('Root', $tab);
        }
        $FacebookPixelId = new TextField('FacebookPixelId');
        $tab->push($FacebookPixelId);
    }

    /**
     * @return bool
     */
    public function shouldRequireFacebookPixel()
    {
        if (!Director::isLive()) {
            return false;
        }
        if (!$this->owner->FacebookPixelId) {
            return false;
        }
        return true;
    }

    /**
     * Suitable for template usage
     * @return ArrayList
     */
    public function FacebookEvents()
    {
        $list = new ArrayList();
        foreach (self::$events as $ev) {
            $list->push([
                'Name' => $ev['name'],
                'JsonParams' => json_encode($ev['params'])
            ]);
        }
        return $list;
    }

    /**
     * @link https://developers.facebook.com/docs/facebook-pixel/reference#standard-events
     * @return array
     */
    public static function listFacebookEvents()
    {
        return [
            'AddPaymentInfo',
            'AddToCart',
            'AddToWishlist',
            'CompleteRegistration',
            'Contact',
            'CustomizeProduct',
            'Donate',
            'FindLocation',
            'InitiateCheckout',
            'Lead',
            'PageView',
            'Purchase',
            'Schedule',
            'Search',
            'StartTrial',
            'SubmitApplication',
            'Subscribe',
            'ViewContent',
        ];
    }

    /**
     * @link https://developers.facebook.com/docs/facebook-pixel/implementation/conversion-tracking
     * @param string $name
     * @param array $params
     * @return void
     */
    public static function trackFacebookEvent($name, $params = [])
    {
        self::$events[] = [
            'name' => $name,
            'params' => $params,
        ];
    }
}
