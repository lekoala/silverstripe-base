<?php

namespace LeKoala\Base\Admin;

use SilverStripe\Core\Convert;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Model\Subsite;
use LeKoala\Base\Subsite\SubsiteExtension;

/**
 */
trait BaseLeftAndMainSubsite
{

    /**
     * Remove subsite access if not on main site
     * Call this in your init method
     *
     * @return void
     */
    public function removeSubsiteFromMenu()
    {
        if (SubsiteHelper::currentSubsiteID()) {
            CMSMenu::remove_menu_item('SilverStripe-Subsites-Admin-SubsiteAdmin');
        }
    }

    public function CurrentSubsite()
    {
        return SubsiteHelper::CurrentSubsite();
    }

    public function ListSubsitesExpanded()
    {
        if (!SubsiteHelper::usesSubsite()) {
            return false;
        }

        $list = Subsite::all_accessible_sites();
        if ($list == null || $list->count() == 1 && $list->first()->DefaultSite == true) {
            return false;
        }

        $currentSubsiteID = SubsiteHelper::currentSubsiteID();

        Requirements::javascript('base/javascript/LeftAndMain_Subsites.js');

        $output = ArrayList::create();

        $siteConfigsBySubsite = Subsite::withDisabledSubsiteFilter(function () {
            $siteConfigs = SiteConfig::get();
            $siteConfigsBySubsite = [];
            foreach ($siteConfigs as $sc) {
                $siteConfigsBySubsite[$sc->SubsiteID] = $sc;
            };
            return $siteConfigsBySubsite;
        });

        /** @var Subsite|SubsiteExtension $subsite */
        foreach ($list as $subsite) {
            $currentState = $subsite->ID == $currentSubsiteID ? 'selected' : '';
            if ($currentState === '' && $subsite->HideFromMenu) {
                continue;
            }

            $SiteConfig = $siteConfigsBySubsite[$subsite->ID] ?? $subsite->SiteConfig();
            $PrimaryColor = $SiteConfig ? $SiteConfig->dbObject('PrimaryColor') : null;

            $output->push(ArrayData::create([
                'CurrentState' => $currentState,
                'ID' => $subsite->ID,
                'Title' => Convert::raw2xml($subsite->Title),
                'BackgroundColor' => $PrimaryColor ? $PrimaryColor->Color() : null,
                'Color' => $PrimaryColor ? $PrimaryColor->ContrastColor() : null,
            ]));
        }

        return $output;
    }
}
