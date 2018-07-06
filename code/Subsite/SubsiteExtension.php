<?php
namespace LeKoala\Base\Subsite;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Improve subsites
 *
 * @property \SilverStripe\Subsites\Model\Subsite|\LeKoala\Base\Subsite\SubsiteExtension $owner
 */
class SubsiteExtension extends DataExtension
{

    /**
     * @return SiteConfig
     */
    public function SiteConfig()
    {
        SubsiteHelper::DisableFilter();
        $config = SiteConfig::get()->filter('SubsiteID', $this->owner->ID)->first();
        SubsiteHelper::RestoreFilter();
        return $config;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $Domains = $fields->dataFieldByName('Domains');
        if ($Domains) {
            $fields->removeByName('Domains');
            $fields->insertAfter('Theme', $Domains);
        }
    }
}
