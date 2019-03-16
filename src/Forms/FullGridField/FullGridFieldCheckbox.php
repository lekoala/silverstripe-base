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
use SilverStripe\Forms\LiteralField;
use LeKoala\Base\Helpers\ClassHelper;

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

    /**
     * Toggle if removing items should be prevented
     * @var bool
     */
    protected $preventRemove = false;

    /**
     * Toggle if we should handle empty post (may be dangerous!)
     * @var bool
     */
    protected $noEmpty = true;

    /**
     * @var array
     */
    protected $cannotBeRemovedIDs = [];

    /**
     * @var bool
     */
    protected $instantSave = false;

    /**
     * Get the value of preventRemove
     * @return bool
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

    /**
     * Get toggle if we should handle empty post (may be dangerous!)
     * @return bool
     */
    public function getNoEmpty()
    {
        return $this->noEmpty;
    }

    /**
     * Set toggle if we should handle empty post (may be dangerous!)
     *
     * @param bool $noEmpty Toggle if we should handle empty post (may be dangerous!)
     * @return $this
     */
    public function setNoEmpty($noEmpty)
    {
        $this->noEmpty = $noEmpty;
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
        // Is handled for each record individually
        if ($this->instantSave) {
            return;
        }

        $post = isset($_POST['FullGridSelect'][$grid->getName()]) ? array_keys($_POST['FullGridSelect'][$grid->getName()]) : [];

        // It's empty, handle only if chosen (it will remove everything!)
        if (empty($post) && $this->noEmpty) {
            return;
        }

        $name = $grid->getName();

        $rel = $this->saveToRelation ? $this->saveToRelation : $name;

        /* @var $list ManyManyList */
        $list = $record->$rel();

        $currentIds = $list->getIDList();

        $toAdd = array_diff($post, $currentIds);
        $toRemove = array_diff($currentIds, $post);

        foreach ($toAdd as $k => $id) {
            $list->add($id);
        }
        if ($this->preventRemove) {
            // Do nothing
        } else {
            foreach ($toRemove as $k => $id) {
                if (in_array($id, $this->cannotBeRemovedIDs)) {
                    // Do nothing
                } else {
                    $list->removeByID($id);
                }
            }
        }
    }

    public function handleInstantSave(GridField $grid, $data)
    {
        $checked = $data['checked'];
        $id = $data['id'];
        $recordInfo = $data['record'];

        if (!$id || !$recordInfo) {
            throw new Exception("No id or no record");
        }

        $recordInfosParts = explode('_', $recordInfo);
        $recordClassName = ClassHelper::unsanitiseClassName($recordInfosParts[0]);
        $recordID = $recordInfosParts[1];

        /* @var $record DataObject */
        $record = $recordClassName::get()->byID($recordID);
        if (!$record) {
            throw new Exception("Record $recordID of class $recordClassName not found");
        }
        if (!$record->canEdit()) {
            throw new Exception("Cannot edit record");
        }

        $name = $grid->getName();

        $rel = $this->saveToRelation ? $this->saveToRelation : $name;

        /* @var $list ManyManyList */
        $list = $record->$rel();

        $msg = "Something wrong happened";
        if ($checked) {
            $list->add($id);
            $msg = "Record added";
        } else {
            if (in_array($id, $this->cannotBeRemovedIDs)) {
                // Do nothing
            } else {
                $list->removeByID($id);
                $msg = "Record removed";
            }
        }

        return $msg;
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
        if (in_array($record->ID, $this->cannotBeRemovedIDs)) {
            return '';
        }

        $cb = CheckboxField::create('FullGridSelect[' . $gridField->getName() . '][' . $record->ID . ']', '')
            ->addExtraClass('FullGridSelect no-change-track');

        if ($this->instantSave) {
            $cb->addExtraClass('FullGridSelect-instantSave');
        }

        if ($this->ids === null) {
            $this->ids = $gridField->getList()->column('ID');
        }

        // Is checked?
        if (in_array($record->ID, $this->ids)) {
            // Can be removed?
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

    /**
     * Get the value of cannotBeRemovedIDs
     * @return array
     */
    public function getCannotBeRemovedIDs()
    {
        return $this->cannotBeRemovedIDs;
    }

    /**
     * Set the value of cannotBeRemovedIDs
     *
     * @param array $cannotBeRemovedIDs
     * @return $this
     */
    public function setCannotBeRemovedIDs($cannotBeRemovedIDs)
    {
        $this->cannotBeRemovedIDs = $cannotBeRemovedIDs;
        return $this;
    }

    /**
     * Get the value of instantSave
     * @return bool
     */
    public function getInstantSave()
    {
        return $this->instantSave;
    }

    /**
     * Set the value of instantSave
     *
     * @param bool $instantSave
     * @return $this
     */
    public function setInstantSave($instantSave)
    {
        $this->instantSave = $instantSave;
        return $this;
    }
}
