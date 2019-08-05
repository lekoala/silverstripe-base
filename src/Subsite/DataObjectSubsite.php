<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\ORM\DataQuery;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;

/**
 * Attach a dataobject to a subsite
 *
 * @property \LeKoala\Base\Subsite\DataObjectSubsite $owner
 * @property int $SubsiteID
 * @method \SilverStripe\Subsites\Model\Subsite Subsite()
 */
class DataObjectSubsite extends DataExtension
{
    private static $has_one = [
        'Subsite' => Subsite::class,
    ];

    /**
     * @return array
     */
    public static function listDataObjectWithSubsites()
    {
        $arr = array();
        $dataobjects = ClassInfo::subclassesFor(DataObject::class);
        foreach ($dataobjects as $dataobject) {
            $singl = singleton($dataobject);
            if ($singl->hasExtension(self::class)) {
                $arr[$dataobject] = $dataobject;
            }
        }
        return $arr;
    }

    /**
     * Update any requests to limit the results to the current site
     * @param SQLSelect $query
     * @param DataQuery|null $dataQuery
     */
    public function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null)
    {
        if (Subsite::$disable_subsite_filter) {
            return;
        }
        if ($dataQuery && $dataQuery->getQueryParam('Subsite.filter') === false) {
            return;
        }

        // If you're querying by ID, ignore the sub-site - this is a bit ugly...
        if ($query->filtersOnID()) {
            return;
        }
        $regexp = '/^(.*\.)?("|`)?SubsiteID("|`)?\s?=/';
        foreach ($query->getWhereParameterised($parameters) as $predicate) {
            if (preg_match($regexp, $predicate)) {
                return;
            }
        }

        $subsiteID = SubsiteState::singleton()->getSubsiteId();
        if ($subsiteID === null) {
            return;
        }

        $froms = $query->getFrom();
        $froms = array_keys($froms);
        $tableName = array_shift($froms);
        $query->addWhere("\"$tableName\".\"SubsiteID\" IN ($subsiteID)");
    }

    public function onBeforeWrite()
    {
        if ((!is_numeric($this->owner->ID) || !$this->owner->ID) && !$this->owner->SubsiteID) {
            $this->owner->SubsiteID = SubsiteState::singleton()->getSubsiteId();
        }
    }

    /**
     * Return a piece of text to keep DataObject cache keys appropriately specific
     */
    public function cacheKeyComponent()
    {
        return 'subsite-' . SubsiteState::singleton()->getSubsiteId();
    }

    public function updateCMSFields(FieldList $fields)
    {
        $SubsiteID = SubsiteState::singleton()->getSubsiteId();

        if ($SubsiteID) {
            // We have a current subsite, add a hidden field to track state
            // Override with owner subsite ID if different
            if ($this->owner->SubsiteID) {
                $SubsiteID = $this->owner->SubsiteID;
            }
            $fields->push(HiddenField::create('SubsiteID', 'SubsiteID', $SubsiteID));
        } else {
            // On main site, allow choosing subsite
            $SubsiteIDField = DropdownField::create('SubsiteID', 'Subsite', Subsite::get()->map());
            $fields->addFieldsToTab('Root.Main', $SubsiteIDField);
            $SubsiteIDField->setHasEmptyDefault(true);
        }
    }

    public function updateSummaryFields(&$fields)
    {
        $SubsiteID = SubsiteState::singleton()->getSubsiteId();
        if (!$SubsiteID) {
            $fields['Subsite.Title'] = 'Subsite';
        }
    }
}
