<?php
namespace LeKoala\Base\Admin;

use SilverStripe\Core\Convert;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Admin\LeftAndMainExtension;

/**
 * Class \LeKoala\Base\LeftAndMainExtension
 *
 * @property \SilverStripe\Admin\LeftAndMain|\SilverStripe\CMS\Controllers\CMSMain|\LeKoala\Base\BaseLeftAndMainExtension $owner
 */
class BaseLeftAndMainExtension extends LeftAndMainExtension
{
    use Configurable;

    public function init()
    {
        $SiteConfig = SiteConfig::current_site_config();
        if ($SiteConfig->ForceSSL) {
            Director::forceSSL();
        }

        // Hide items if necessary, example yml:
        //
        // LeKoala\Base\Admin\BaseLeftAndMainExtension:
        //   removed_items:
        //      - SilverStripe-CampaignAdmin-CampaignAdmin
        //
        // This is just hiding stuff, a more robust solution would be to not install these things
        $removedItems = self::config()->removed_items;
        if ($removedItems) {
            $css = '';
            foreach ($removedItems as $item) {
                CMSMenu::remove_menu_item($item);
                $itemParts = explode('-', $item);
                $css .= 'li.valCMS_ACCESS_' . end($itemParts) . '{display:none}' . "\n";
            }
            Requirements::customCSS($css, 'HidePermissions');
        }

        // Remove subsite access if not on main site
        if (SubsiteHelper::CurrentSubsiteID()) {
            CMSMenu::remove_menu_item('SilverStripe-Subsites-Admin-SubsiteAdmin');
        }

        // Check if we need font awesome (if any item use IconClass fa fa-something)
        // eg: private static $menu_icon_class = 'fa fa-calendar';
        // @link https://fontawesome.com/v4.7.0/cheatsheet/
        $items = $this->owner->MainMenu();
        foreach ($items as $item) {
            if (strpos($item->IconClass, 'fa fa-') === 0) {
                $this->requireFontAwesome();
            }
        }
    }

    public function requireFontAwesome()
    {
        Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
        // Fix icon size
        // Moved to admin.css
        // Requirements::customCSS(".menu__icon.fa { font-size: 17px !important}", "FontAwesomeMenuIcons");
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

        $currentSubsiteID = SubsiteHelper::CurrentSubsiteID();

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
