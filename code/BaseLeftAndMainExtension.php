<?php
namespace LeKoala\Base;

use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Admin\CMSMenu;

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
        $removedItems = self::config()->removed_items;
        if ($removedItems) {
            foreach ($removedItems as $item) {
                CMSMenu::remove_menu_item($item);
            }
        }

        $items = CMSMenu::get_menu_items();
    }
}
