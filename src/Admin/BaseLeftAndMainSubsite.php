<?php
namespace LeKoala\Base\Admin;

use SilverStripe\Core\Convert;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\Subsites\Model\Subsite;

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

        foreach ($list as $subsite) {
            $currentState = $subsite->ID == $currentSubsiteID ? 'selected' : '';

            $SiteConfig = $subsite->SiteConfig();
            if (!$SiteConfig) {
                continue;
            }
            $PrimaryColor = $SiteConfig->dbObject('PrimaryColor');

            $output->push(ArrayData::create([
                'CurrentState' => $currentState,
                'ID' => $subsite->ID,
                'Title' => Convert::raw2xml($subsite->Title),
                'BackgroundColor' => $PrimaryColor->Color(),
                'Color' => $PrimaryColor->ContrastColor(),
            ]));
        }

        return $output;
    }
}
