<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DataObject;
use SilverShop\HasOneField\HasOneButtonField;
use SilverShop\HasOneField\GridFieldSummaryField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverShop\HasOneField\GridFieldHasOneButtonRow;
use SilverShop\HasOneField\GridFieldHasOneEditButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;

/**
 * A simpler HasOneButtonField
 */
class SimpleHasOneButtonField extends HasOneButtonField
{
    /**
     * @param \SilverStripe\ORM\DataObject $parent
     * @param string $relationName
     * @param string|null $fieldName
     * @param string|null $title
     * @param GridFieldConfig|null $customConfig
     * @param boolean|null $useAutocompleter
     */
    public function __construct(DataObject $parent, $relationName, $fieldName = null, $title = null, GridFieldConfig $customConfig = null, $useAutocompleter = false)
    {
        if ($customConfig === null) {
            $customConfig = GridFieldConfig::create()
                ->addComponent(new GridFieldHasOneButtonRow())
                ->addComponent(new GridFieldSummaryField($relationName))
                ->addComponent(new GridFieldDetailForm())
                ->addComponent(new GridFieldHasOneEditButton('buttons-before-right'));
        }

        parent::__construct($parent, $relationName, $fieldName, $title, $customConfig, $useAutocompleter);
    }
}
