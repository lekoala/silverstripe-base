<?php
namespace LeKoala\Base\Forms\FullGridField;

use SilverStripe\ORM\SS_List;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use LeKoala\Base\Forms\FullGridField\FullGridFieldCheckbox;
use LeKoala\Base\Forms\FullGridField\FullGridFieldEditButton;
use LeKoala\Base\Forms\FullGridField\FullGridFieldQuickFilter;

/**
 * The full grid field allows to display a list of data and tick them using checkboxes
 * A bit like an very extended CheckboxSetField
 *
 * @author Koala
 */
class FullGridField extends GridField
{

    public function __construct($name, $title = null, SS_List $dataList = null, GridFieldConfig $config = null)
    {

        if (!$config) {
            $config = $this->createDefaultConfig();
        }

        parent::__construct($name, $title, $dataList, $config);
    }

    /**
     * Filters to apply to the list if no sql select is provided
     *
     * @param array $filters
     * @return void
     */
    public function setFilters($filters)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setFilters($filters);
    }

    /**
     * Shorthand for setting relation to target instead of current name
     *
     * @param string $relation
     * @return void
     */
    public function setSaveToRelation($relation)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setSaveToRelation($relation);
    }

    /**
     * Shorthand for setting sql select
     *
     * @param SQLSelect $select
     * @return void
     */
    public function setSqlSelect($select)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setSqlSelect($select);
    }

    /**
     * @return bool
     */
    public function getPreventRemove()
    {
        return $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->getPreventRemove();
    }

    /**
     * Prevent removing records even if checkboxes are unticked
     *
     * @return  self
     */
    public function setPreventRemove($preventRemove)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setPreventRemove($preventRemove);
    }



    public function createDefaultConfig()
    {
        $config = new GridFieldConfig();

        $config->addComponent(new GridFieldToolbarHeader());
        $config->addComponent($sort = new GridFieldTitleHeader());
        $config->addComponent($sort = new FullGridFieldQuickFilter());
//        $config->addComponent($filter     = new GridFieldFilterHeader());
        $config->addComponent(new GridFieldDataColumns());
//        $config->addComponent(new GridFieldPageCount('toolbar-header-right'));
//        $config->addComponent($pagination = new GridFieldPaginator(50));
        $config->addComponent(new FullGridFieldCheckbox);
        $config->addComponent(new GridFieldDetailForm);
        $config->addComponent(new FullGridFieldEditButton);

//        $sort->setThrowExceptionOnBadDataType(false);
//        $filter->setThrowExceptionOnBadDataType(false);
//        $pagination->setThrowExceptionOnBadDataType(false);

        return $config;
    }
}
