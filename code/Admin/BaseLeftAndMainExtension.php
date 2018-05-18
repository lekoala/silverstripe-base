<?php
namespace LeKoala\Base\Admin;

use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\View\Requirements;

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
        // Hide items if necessary
        $removedItems = self::config()->removed_items;
        if ($removedItems) {
            foreach ($removedItems as $item) {
                CMSMenu::remove_menu_item($item);
            }
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
        Requirements::customCSS(".menu__icon.fa { font-size: 17px !important}", "FontAwesomeMenuIcons");
    }
}
