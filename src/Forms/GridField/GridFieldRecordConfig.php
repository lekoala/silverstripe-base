<?php
namespace LeKoala\Base\Forms\GridField;

use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;

/**
 * Grouping is ugly and makes edit button big and the whole layout jumpy
 * Delete should not unlink
 */
class GridFieldRecordConfig extends GridFieldConfig_RecordEditor
{
    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct();

        $this->removeComponentsByType(GridField_ActionMenu::class);

        // use proper delete action
        $deleteAction = $this->getComponentByType(GridFieldDeleteAction::class);
        if ($deleteAction) {
            $this->removeComponentsByType(GridFieldDeleteAction::class);
            $this->addComponent(new GridFieldDeleteAction(false));
        }
    }
}
