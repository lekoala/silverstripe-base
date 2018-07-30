<?php
namespace LeKoala\Base\Forms\FullGridField;

use SilverStripe\Forms\GridField\GridField_HTMLProvider;

class FullGridFieldQuickFilter implements GridField_HTMLProvider
{

    /**
     * The HTML fragment to write this component into
     */
    protected $targetFragment;

    /**
     *
     * @param array $searchFields Which fields on the object in the list should be searched
     */
    public function __construct($targetFragment = 'before')
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     *
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $name = $gridField->getName() . 'QuickFilter';

        $placeholder = _t('FullGridFieldQuickFilter.QUICKLY_FILTER', 'Quickly filter this list by keywords');
        $filterInput = '<input type="text" name="' . $name . '" id="' . $name . '" '
            . 'class="no-change-track text FullGridFieldQuickFilter" placeholder="' . $placeholder . '" '
            . 'data-grid="' . $gridField->getName() . '"'
            . '/>';

        return [
            $this->targetFragment => $filterInput
        ];
    }
}
