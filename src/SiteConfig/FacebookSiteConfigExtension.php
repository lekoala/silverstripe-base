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
 * Facebook SiteConfig stuff
 *
 * SilverStripe\SiteConfig\SiteConfig:
 *   extensions:
 *     - LeKoala\Base\SiteConfig\FacebookSiteConfigExtension
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\LeKoala\Base\SiteConfig\FacebookSiteConfigExtension $owner
 * @property string $FacebookPixelId
 */
class FacebookSiteConfigExtension extends DataExtension
{
    private static $db = [
        "FacebookPixelId" => "Varchar(59)",
    ];

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
}
