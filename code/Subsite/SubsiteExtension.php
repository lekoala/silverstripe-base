<?php
namespace LeKoala\Base\Subsite;

use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Model\SubsiteDomain;

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

    public function onBeforeWrite()
    {
        if ($this->owner->ID && Director::isDev()) {
            $this->addLocalDomain();
        }
    }

    /**
     * @return SubsiteDomain
     */
    public function getLocalDomain()
    {
        return $this->owner->Domains()->where("Domain LIKE '%.local'")->first();
    }

    /**
     * @return bool
     */
    public function addLocalDomain()
    {
        $localDomain = $this->getLocalDomain();
        if ($localDomain) {
            return false;
        }

        $primaryDomain = $this->owner->domain();
        $parts = explode('.', $primaryDomain);
        array_pop($parts);
        array_push($parts, 'local');
        $subsiteDomain = implode('.', $parts);
        // Add port if used
        if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80) {
            $subsiteDomain .= ':' . $_SERVER['SERVER_PORT'];
        }

        $domain = new SubsiteDomain();
        $domain->IsPrimary = 0;
        $domain->SubsiteID = $this->owner->ID;
        $domain->Protocol = 'automatic';
        $domain->Domain = $subsiteDomain;
        return $domain->write();
    }
}
