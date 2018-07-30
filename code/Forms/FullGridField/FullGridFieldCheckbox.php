<?php
namespace LeKoala\Base\Forms\FullGridField;

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_SaveHandler;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_DataManipulator;

/**
 * The checkbox handles adding or removing the record to the relation
 * It will save by default to a relation matching the grid name, but you can target other relations using setSaveToRelation
 */
class FullGridFieldCheckbox implements GridField_SaveHandler, GridField_ColumnProvider, GridField_HTMLProvider, GridField_DataManipulator
{

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var string
     */
    protected $saveToRelation = null;

    /**
     *
     * @var SQLSelect
     */
    protected $sqlSelect = null;

    /**
     * A simple cache
     * @var array
     */
    protected $ids = null;
    protected $preventRemove;

    /**
     * Get the value of preventRemove
     */
    public function getPreventRemove()
    {
        return $this->preventRemove;
    }

    /**
     * Set the value of preventRemove
     *
     * @return $this
     */
    public function setPreventRemove($preventRemove)
    {
        $this->preventRemove = $preventRemove;

        return $this;
    }

    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        $class = $gridField->getModelClass();

        if ($this->sqlSelect) {
            $list = new ArrayList(iterator_to_array($this->sqlSelect->execute()));
        } else {
            $list = $class::get();
            if ($this->filters) {
                $list = $list->filter($this->filters);
            }
        }

        return $list;
    }


    public function handleSave(GridField $grid, DataObjectInterface $record)
    {
//    l('Handling grid ' . $grid->getName());
        $post = isset($_POST['FullGridSelect'][$grid->getName()]) ? array_keys($_POST['FullGridSelect'][$grid->getName()]) : [];

        if (empty($post)) {
//      l('Grid is empty');
        }

        $name = $grid->getName();

        $rel = $this->saveToRelation ? $this->saveToRelation : $name;

    /* @var $list ManyManyList */
        $list = $record->$rel();

        $currentIds = $list->getIDList();

        $toAdd = array_diff($post, $currentIds);
        $toRemove = array_diff($currentIds, $post);

        foreach ($toAdd as $k => $id) {
//      l('Add company ' . $id);
            $list->add($id);
        }
        if ($grid->hasMethod('getPreventRemove') && $grid->getPreventRemove()) {
        } else {
            foreach ($toRemove as $k => $id) {
        //      l('Remove company ' . $id);
                $list->removeByID($id);
            }
        }
    }

    /**
     * @param GridField $gridField
     *
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        Requirements::css('base/css/FullGridField.css');
        Requirements::javascript('base/javascript/FullGridField.js');
    }

    /**
     * Add bulk select column.
     *
     * @param GridField $gridField Current GridField instance
     * @param array   $columns  Columns list
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('FullGridSelect', $columns)) {
            array_unshift($columns, 'FullGridSelect');
        }
    }

    /**
     * Which columns are handled by the component.
     *
     * @param GridField $gridField Current GridField instance
     *
     * @return array List of handled column names
     */
    public function getColumnsHandled($gridField)
    {
        return array('FullGridSelect');
    }

    /**
     * Sets the column's content.
     *
     * @param GridField $gridField Current GridField instance
     * @param DataObject $record   Record intance for this row
     * @param string   $columnName Column's name for which we need content
     *
     * @return mixed Column's field content
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $cb = CheckboxField::create('FullGridSelect[' . $gridField->getName() . '][' . $record->ID . ']', '')
            ->addExtraClass('FullGridSelect no-change-track');

        if ($this->ids === null) {
            $this->ids = $gridField->getList()->column('ID');
        }

    // Is checked?
        if (in_array($record->ID, $this->ids)) {
            $cb->setValue(1);
        }

        return $cb->Field();
    }

    /**
     * Set the column's HTML attributes.
     *
     * @param GridField $gridField Current GridField instance
     * @param DataObject $record   Record intance for this row
     * @param string   $columnName Column's name for which we need attributes
     *
     * @return array List of HTML attributes
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return array('class' => 'col-FullGridSelect');
    }

    /**
     * Set the column's meta data.
     *
     * @param GridField $gridField Current GridField instance
     * @param string  $columnName Column's name for which we need meta data
     *
     * @return array List of meta data
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'FullGridSelect') {
            return array('title' => '');
        }
    }

    /**
     * Get the value of saveToRelation
     *
     * @return string
     */
    public function getSaveToRelation()
    {
        return $this->saveToRelation;
    }

    /**
     * Set the value of saveToRelation
     *
     * @param string $saveToRelation
     *
     * @return $this
     */
    public function setSaveToRelation(string $saveToRelation)
    {
        $this->saveToRelation = $saveToRelation;

        return $this;
    }

    /**
     * Get the value of filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set the value of filters
     *
     * @param array $filters
     *
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get the value of sqlSelect
     *
     * @return SQLSelect
     */
    public function getSqlSelect()
    {
        return $this->sqlSelect;
    }

    /**
     * Set the value of sqlSelect
     *
     * @param SQLSelect $sqlSelect
     * @return $this
     */
    public function setSqlSelect($sqlSelect)
    {
        $this->sqlSelect = $sqlSelect;

        return $this;
    }
}
