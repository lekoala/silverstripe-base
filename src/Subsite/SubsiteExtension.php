<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Model\SubsiteDomain;
use SilverStripe\ORM\DataObject;

/**
 * Improve subsites
 *
 * @property \LeKoala\Base\Subsite\SubsiteExtension $owner
 */
class SubsiteExtension extends DataExtension
{
    /**
     * @var boolean
     */
    public static $delete_related = true;

    /**
     * @return SiteConfig
     */
    public function SiteConfig()
    {
        SubsiteHelper::disableFilter();
        $config = SiteConfig::get()->filter('SubsiteID', $this->owner->ID)->first();
        SubsiteHelper::restoreFilter();
        return $config;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $Domains = $fields->dataFieldByName('Domains');
        if ($Domains) {
            $fields->removeByName('Domains');
            $Theme = $fields->dataFieldByName('Theme');
            if ($Theme) {
                $fields->insertAfter('Theme', $Domains);
            } else {
                $fields->addFieldsToTab('Root.Main', $Domains);
            }
        }
    }

    public function onBeforeWrite()
    {
        if ($this->owner->ID && Director::isDev()) {
            $this->addLocalDomain();
        }
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        if (self::$delete_related) {
            // Delete SiteConfig
            $SiteConfig = SiteConfig::get()->filter('SubsiteID', $this->owner->ID)->first();
            if ($SiteConfig) {
                $SiteConfig->delete();
            }

            // Ripple delete any objects
            foreach (DataObjectSubsite::listDataObjectWithSubsites() as $class) {
                $list = $class::get()->filter('SubsiteID', $this->owner->ID);
                foreach ($list as $item) {
                    $item->delete();
                }
            }
        }
    }

    /**
     * @return SubsiteDomain
     */
    public function getLocalDomain()
    {
        return $this->owner->Domains()->where("Domain LIKE '%.local%'")->first();
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
        // Port are ignored in newest versions
        // if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80) {
        //     $subsiteDomain .= ':' . $_SERVER['SERVER_PORT'];
        // }

        $domain = new SubsiteDomain();
        $domain->IsPrimary = 0;
        $domain->SubsiteID = $this->owner->ID;
        $domain->Protocol = 'automatic';
        $domain->Domain = $subsiteDomain;
        return $domain->write();
    }
}
