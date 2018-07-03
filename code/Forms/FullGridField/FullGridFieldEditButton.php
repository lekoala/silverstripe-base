<?php
namespace LeKoala\Base\Forms\FullGridField;

use SilverStripe\View\SSViewer;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridFieldEditButton;

/**
 * Selected records can be edited with this button
 */
class FullGridFieldEditButton extends GridFieldEditButton
{

    protected $ids = null;

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     *
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($this->ids === null) {
            $this->ids = $gridField->getList()->column('ID');
        }

        // Is checked?
        if (in_array($record->ID, $this->ids)) {
            $data = new ArrayData(array(
                'Link' => Controller::join_links($gridField->Link('item'), $record->ID, 'edit'),
                'ExtraClass' => $this->getExtraClass()
            ));

            $template = SSViewer::get_templates_by_class($this, '', GridFieldEditButton::class);
            return $data->renderWith($template);
        }
    }
}
