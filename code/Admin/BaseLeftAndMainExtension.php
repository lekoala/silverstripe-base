<?php
namespace LeKoala\Base\Admin;

use SilverStripe\i18n\i18n;
use Psr\Log\LoggerInterface;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Admin\LeftAndMainExtension;

/**
 * Class \LeKoala\Base\LeftAndMainExtension
 *
 * @property \SilverStripe\Admin\ModelAdmin|\SilverStripe\Admin\SecurityAdmin|\SilverStripe\SiteConfig\SiteConfigLeftAndMain|\SilverStripe\AssetAdmin\Controller\AssetAdmin|\SilverStripe\CMS\Controllers\CMSMain|\SilverStripe\CMS\Controllers\CMSPageAddController|\SilverStripe\CMS\Controllers\CMSPagesController|\SilverStripe\Subsites\Admin\SubsiteAdmin|\LeKoala\Base\Admin\BaseLeftAndMainExtension $owner
 */
class BaseLeftAndMainExtension extends LeftAndMainExtension
{
    use Configurable;
    use BaseLeftAndMainSubsite;

    /**
     * @config
     * @var boolean
     */
    private static $dark_theme = false;

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

        $this->removeSubsiteFromMenu();

        // Check if we need font awesome (if any item use IconClass fa fa-something)
        // eg: private static $menu_icon_class = 'fa fa-calendar';
        // @link https://fontawesome.com/v4.7.0/cheatsheet/
        $items = $this->owner->MainMenu();
        foreach ($items as $item) {
            if (strpos($item->IconClass, 'fa fa-') === 0) {
                $this->requireFontAwesome();
            }
        }

        // if (isset($_GET['locale'])) {
        //     i18n::set_locale($_GET['locale']);
        // }

        if (self::config()->dark_theme) {
            Requirements::css('base/css/admin-dark.css');
        }

        Requirements::javascript("base/javascript/admin.js");
        $this->requireAdminStyles();
    }

    /**
     * This styles the top left box to match the current theme
     *
     * @return void
     */
    public function requireAdminStyles()
    {
        $SiteConfig = SiteConfig::current_site_config();
        if (!$SiteConfig->PrimaryColor) {
            return;
        }

        $PrimaryColor = $SiteConfig->dbObject('PrimaryColor');

        $bg = $PrimaryColor->Color();
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

    public function requireFontAwesome()
    {
        Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
        // Fix icon size
        // Moved to admin.css
        // Requirements::customCSS(".menu__icon.fa { font-size: 17px !important}", "FontAwesomeMenuIcons");
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return Injector::inst()->get(LoggerInterface::class)->withName('Admin');
    }
}
