<?php
namespace LeKoala\Base\Actions;

use SilverStripe\Control\Controller;
use LeKoala\Base\Actions\CustomButton;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\Base\Forms\GridField\GridFieldRowLink;

/**
 * Expose a custom link in a GridField at row level
 * Action must be declared in getCMSActions
 */
class GridFieldCustomLink extends GridFieldRowLink
{
    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string The link to the action
     */
    public function getLink($gridField, $record, $columnName)
    {
        return Controller::join_links($gridField->Link('item'), $record->ID, 'doCustomLink') . '?CustomLink=' . $this->name;
    }
}
