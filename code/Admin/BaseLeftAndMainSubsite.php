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

    public function requireSubsiteAdminStyles()
    {
        $subsite = self::CurrentSubsite();
        if (!$subsite) {
            return;
        }

        $PrimaryColor = $subsite->SiteConfig()->dbObject('PrimaryColor');

        $bg = $PrimaryColor->getValue();
        $border = $PrimaryColor->HighlightColor();

        $styles = <<<CSS
.cms-menu__header {background: $bg}
.cms-sitename {border-color: $border}
.cms-sitename:focus, .cms-sitename:hover {background-color: $border}
.cms-login-status .cms-login-status__profile-link:focus, .cms-login-status .cms-login-status__profile-link:hover {background-color: $border}
.cms-login-status .cms-login-status__logout-link:focus, .cms-login-status .cms-login-status__logout-link:hover {background-color: $border}
CSS;
        Requirements::customCSS($styles);
    }

    public function CurrentSubsite()
    {
        return SubsiteHelper::CurrentSubsite();
    }

    public function ListSubsitesExpanded()
    {
        if (!SubsiteHelper::UsesSubsite()) {
            return false;
        }

        $list = Subsite::all_accessible_sites();
        if ($list == null || $list->count() == 1 && $list->first()->DefaultSite == true) {
            return false;
        }

        $currentSubsiteID = SubsiteHelper::currentSubsiteID();

        Requirements::javascript('silverstripe/subsites:javascript/LeftAndMain_Subsites.js');

        $output = ArrayList::create();

        foreach ($list as $subsite) {
            $currentState = $subsite->ID == $currentSubsiteID ? 'selected' : '';

            $color = $subsite->SiteConfig()->dbObject('PrimaryColor');

            $output->push(ArrayData::create([
                'CurrentState' => $currentState,
                'ID' => $subsite->ID,
                'Title' => Convert::raw2xml($subsite->Title),
                'BackgroundColor' => $color->Color(),
                'Color' => $color->ContrastColor(),
            ]));
        }

        return $output;
    }
}
