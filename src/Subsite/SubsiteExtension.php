<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Model\SubsiteDomain;

/**
 * Improve subsites
 *
 * @property \SilverStripe\Subsites\Model\Subsite|\LeKoala\Base\Subsite\SubsiteExtension $owner
 * @property bool|int $IgnoreDefaultPages
 * @extends \SilverStripe\Core\Extension<object>
 */
class SubsiteExtension extends Extension
{
    /**
     * @var array<string,string>
     */
    private static $db = [
        'IgnoreDefaultPages' => 'Boolean',
    ];

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
                $fields->addFieldToTab('Root.Main', $Domains);
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
