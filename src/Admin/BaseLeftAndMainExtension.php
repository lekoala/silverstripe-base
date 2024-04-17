<?php

namespace LeKoala\Base\Admin;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\View\SSViewer;
use SilverStripe\Core\Environment;
use SilverStripe\View\Requirements;
use LeKoala\Base\Security\Antivirus;
use LeKoala\Multilingual\LangHelper;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use LeKoala\DeferBackend\CspProvider;
use SilverStripe\Security\Permission;
use LeKoala\DeferBackend\DeferBackend;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Injector\Injector;
use LeKoala\Base\View\CommonRequirements;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Admin\LeftAndMainExtension;
use LeKoala\Base\Theme\ThemeSiteConfigExtension;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\TinyMCECombinedGenerator;

/**
 * Available config
 *
 * LeKoala\Base\Admin\BaseLeftAndMainExtension:
 * dark_theme: true
 * help_enabled: false
 * removed_items:
 *   - SilverStripe-CampaignAdmin-CampaignAdmin
 *   ...
 *
 * @property \SilverStripe\Admin\LeftAndMain|\LeKoala\Base\Admin\BaseLeftAndMainExtension $owner
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

    /**
     * @config
     * @var boolean
     */
    private static $help_enabled = true;

    /**
     * @config
     * @var array<string>
     */
    private static $removed_items = [];

    /**
     * @config
     * @var array<string>
     */
    private static $reordered_items = [];

    public function init(): void
    {
        $SiteConfig = SiteConfig::current_site_config();

        // Never have comments as it can break ajax requests
        // Config::modify()->set(SSViewer::class, 'source_file_comments', false);

        LangHelper::persistLocale();

        $this->removeMenuItems();
        $this->reorderMenuItems();
        $this->removeSubsiteFromMenu();

        // Check if we need font awesome (if any item use IconClass fa fa-something)
        // eg: private static $menu_icon_class = 'fa fa-calendar';
        $items = $this->owner->MainMenu();
        foreach ($items as $item) {
            if (strpos($item->IconClass, 'fa fa-') !== false) {
                CommonRequirements::fontAwesome4();
            } elseif (strpos($item->IconClass, 'fas fa-') !== false) {
                CommonRequirements::fontAwesome5();
            } elseif (strpos($item->IconClass, 'bx bx-') !== false) {
                CommonRequirements::boxIcons();
            } elseif (strpos($item->IconClass, 'bx bxs-') !== false) {
                CommonRequirements::boxIcons();
            }
        }

        $this->includeLastIcon();

        // otherwise it may show artefacts when loading
        if (Environment::getEnv('DONT_FORCE_TINYMCE_LOAD')) {
            // Keep default
        } else {
            self::forceTinyMCELoad();
        }

        if (self::config()->dark_theme) {
            Requirements::css('base/css/admin-dark.css');
        }

        Requirements::javascript("base/javascript/admin.js");
        $this->requireAdminStyles();
    }

    public function ShowAVWarning(): bool
    {
        if (Antivirus::isConfigured() && !Antivirus::isConfiguredAndWorking()) {
            return true;
        }
        return false;
    }

    /**
     * This can be required if some resources are loaded from a cdn
     * and you get tinymce is undefined
     *
     * @return void
     */
    public static function forceTinyMCELoad()
    {
        // This needs to be loaded after #assetadmin because it depends on InsertMediaModal to be defined
        $cmsConfig = HTMLEditorConfig::get('cms');
        $generator = Injector::inst()->get(TinyMCECombinedGenerator::class);
        $link = $generator->getScriptURL($cmsConfig);
        Requirements::javascript($link);
    }

    /**
     * @return DBHTMLText
     */
    public function BaseMenu()
    {
        return $this->owner->renderWith($this->owner->getTemplatesWithSuffix('_BaseMenu'));
    }

    /**
     * Hide items if necessary, example yml:
     *
     *    LeKoala\Base\Admin\BaseLeftAndMainExtension:
     *      removed_items:
     *        - SilverStripe-CampaignAdmin-CampaignAdmin
     *
     * @return void
     */
    protected function removeMenuItems()
    {
        $removedItems = self::config()->removed_items;
        if ($removedItems) {
            $css = '';
            foreach ($removedItems as $item) {
                CMSMenu::remove_menu_item($item);
                $itemParts = explode('-', $item);
                // asset admin is required for upload field details
                if ($item != 'SilverStripe-AssetAdmin-Controller-AssetAdmin') {
                    // @link https://github.com/silverstripe/silverstripe-framework/pull/10663
                    $css .= 'li.valCMS_ACCESS_' . $item . '{display:none}' . "\n";
                    $css .= 'li.valCMS_ACCESS_' . end($itemParts) . '{display:none}' . "\n";
                }
            }
            // maybe it would be better to use SilverStripe\\Security\\Permission::hidden_permissions
            Requirements::customCSS($css, 'HidePermissions');
        }
    }

    /**
     * Reorder menu items based on a given array
     *
     * @return void
     */
    protected function reorderMenuItems()
    {
        $reorderedItems = self::config()->reordered_items;
        if (empty($reorderedItems)) {
            return;
        }

        $currentMenuItems = CMSMenu::get_menu_items();
        CMSMenu::clear_menu();
        // Let's add items in the given order
        $priority = 500;
        foreach ($reorderedItems as $itemName) {
            foreach ($currentMenuItems as $key => $item) {
                if ($key != $itemName) {
                    continue;
                }
                $item->priority = $priority;
                $priority -= 10;
                CMSMenu::add_menu_item($key, $item->title, $item->url, $item->controller, $item->priority);
                unset($currentMenuItems[$key]);
            }
        }
        // Add remaining items
        foreach ($currentMenuItems as $key => $item) {
            CMSMenu::add_menu_item($key, $item->title, $item->url, $item->controller, $item->priority);
        }
    }

    /**
     * This styles the top left box to match the current theme
     *
     * @return void
     */
    public function requireAdminStyles()
    {
        /** @var SiteConfig|ThemeSiteConfigExtension $SiteConfig */
        $SiteConfig = SiteConfig::current_site_config();
        if (!$SiteConfig->PrimaryColor) {
            return;
        }

        $PrimaryColor = $SiteConfig->dbObject('PrimaryColor');

        $bg = $PrimaryColor->Color();
        // Black is too harsh so we use a softer shadow
        $color = $PrimaryColor->ContrastColor('#333');
        $border = $PrimaryColor->HighlightColor();

        $styles = <<<CSS
.cms-menu__header {background: $bg; color: $color}
.cms-menu__header a, .cms-menu__header span {color: $color !important}
.cms-sitename {border-color: $border}
.cms-sitename:focus, .cms-sitename:hover {background-color: $border}
.cms-login-status .cms-login-status__profile-link:focus, .cms-login-status .cms-login-status__profile-link:hover {background-color: $border}
.cms-login-status .cms-login-status__logout-link:focus, .cms-login-status .cms-login-status__logout-link:hover {background-color: $border}
CSS;
        Requirements::customCSS($styles, 'AdminMenuStyles');
    }

    /**
     * @return void
     */
    protected function includeLastIcon()
    {
        if (!class_exists(LeKoala\Admini\LeftAndMain::class)) {
            return;
        }
        $preconnect = <<<HTML
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
HTML;
        Requirements::insertHeadTags($preconnect, "googlefontspreconnect");

        // Could also host locally https://marella.me/material-icons/demo/#two-tone
        Requirements::css("https://fonts.googleapis.com/icon?family=Material+Icons+Two+Tone");
        Requirements::javascript('lekoala/silverstripe-admini: client/js/last-icon.min.js', ["type" => "application/javascript"]);

        $nonce = '';
        if (class_exists(\LeKoala\DeferBackend\CspProvider::class)) {
            $nonce = \LeKoala\DeferBackend\CspProvider::getCspNonce();
        }
        $lastIconScript = <<<JS
<script nonce="$nonce">
    window.LastIcon = {
            types: {
            material: "twotone",
            },
            defaultSet: "material",
            fonts: ["material"],
        };
</script>
JS;
        Requirements::insertHeadTags($lastIconScript, __FUNCTION__);
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return Injector::inst()->get(LoggerInterface::class)->withName('Admin');
    }

    /**
     * Get the currently edited id from the url
     *
     * @param int $idx The level (0 first level, 1 first sublevel...)
     * @return int The id or 0 if not found
     */
    public function getCurrentRecordID($idx = 0)
    {
        $url = $this->owner->getRequest()->getURL();
        $matches = null;
        preg_match_all('/\/item\/(\d+)/', $url, $matches);
        if (isset($matches[1][$idx])) {
            return $matches[1][$idx];
        }
        return 0;
    }

    /**
     * @return bool
     */
    public function IsHelpEnabled()
    {
        return self::config()->help_enabled;
    }
}
