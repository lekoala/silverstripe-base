<?php

namespace LeKoala\Base\Forms\GridField;

use LogicException;
use ReflectionObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataList;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * see https://github.com/silverstripe/silverstripe-framework/pull/9991
 */
class BetterGridFieldAddExistingAutocompleter extends GridFieldAddExistingAutocompleter
{
    /**
     * @var array
     */
    protected $extraFields = [];

    /**
     * @var string
     */
    protected $defaultSearchFilter = 'StartsWith';

    /**
     * @var boolean
     */
    protected $wildcardMatch = false;

    /**
     * Access a protected property when the api does not allow access
     *
     * @param object $object
     * @param string $property
     * @return mixed
     */
    protected static function getProtectedValue($object, $property)
    {
        $refObject = new ReflectionObject($object);
        $refProperty = $refObject->getProperty($property);
        $refProperty->setAccessible(true);
        return $refProperty->getValue($object);
    }

    /**
     * Easily replace the default autocompleter in a config with this one
     * Returns the new instance in order to add more settings
     *
     * @param GridFieldConfig $config
     * @return $this
     */
    public static function replaceInConfig(GridFieldConfig $config)
    {
        /** @var GridFieldAddExistingAutocompleter $inst  */
        $inst = $config->getComponentByType(GridFieldAddExistingAutocompleter::class);

        // You know, because adding getters is for beginners
        $targetFragment = self::getProtectedValue($inst, "targetFragment");

        $newInst = new BetterGridFieldAddExistingAutocompleter($targetFragment);

        $newInst->setResultsFormat($inst->getResultsFormat());
        $newInst->setResultsLimit($inst->getResultsLimit());
        $newInst->setSearchFields($inst->getSearchFields());

        $config->removeComponent($inst);
        $config->addComponent($newInst);

        return $newInst;
    }

    /**
     * Returns a json array of a search results that can be used by for example Jquery.ui.autosuggestion
     *
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return string
     */
    public function doSearch($gridField, $request)
    {
        $dataClass = $gridField->getModelClass();

        /** @var DataList $allList  */
        $allList = $this->searchList ? $this->searchList : DataList::create($dataClass);

        $searchFields = ($this->getSearchFields())
            ? $this->getSearchFields()
            : $this->scaffoldSearchFields($dataClass);
        if (!$searchFields) {
            throw new LogicException(
                sprintf(
                    'GridFieldAddExistingAutocompleter: No searchable fields could be found for class "%s"',
                    $dataClass
                )
            );
        }

        $params = [];
        foreach ($searchFields as $searchField) {
            $name = (strpos($searchField, ':') !== false) ? $searchField : "$searchField:" . $this->defaultSearchFilter;

            $searchValue =  $request->getVar('gridfield_relationsearch');
            if ($this->wildcardMatch && $this->defaultSearchFilter == "PartialMatch") {
                $searchValue = str_replace(" ", "%", $searchValue);
            }
            $params[$name] = $searchValue;
        }
        $results = $allList
            ->subtract($gridField->getList())
            ->filterAny($params)
            ->sort(strtok($searchFields[0], ':'), 'ASC')
            ->limit($this->getResultsLimit());

        $json = [];
        Config::nest();
        SSViewer::config()->update('source_file_comments', false);
        $viewer = SSViewer::fromString($this->resultsFormat);
        foreach ($results as $result) {
            $title = Convert::html2raw($viewer->process($result));
            $json[] = [
                'label' => $title,
                'value' => $title,
                'id' => $result->ID,
            ];
        }
        Config::unnest();
        $response = new HTTPResponse(json_encode($json));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }


    /**
     * Detect searchable fields and searchable relations.
     * Falls back to {@link DataObject->summaryFields()} if
     * no custom search fields are defined.
     *
     * @param string $dataClass The class name
     * @return array|null names of the searchable fields
     */
    public function scaffoldSearchFields($dataClass)
    {
        $obj = DataObject::singleton($dataClass);
        $fields = null;
        if ($fieldSpecs = $obj->searchableFields()) {
            $customSearchableFields = $obj->config()->get('searchable_fields');
            foreach ($fieldSpecs as $name => $spec) {
                if (is_array($spec) && array_key_exists('filter', $spec)) {
                    // The searchableFields() spec defaults to PartialMatch,
                    // so we need to check the original setting.
                    // If the field is defined $searchable_fields = array('MyField'),
                    // then default to StartsWith filter, which makes more sense in this context.
                    if (!$customSearchableFields || array_search($name, $customSearchableFields)) {
                        $filter = 'StartsWith';
                    } else {
                        $filterName = $spec['filter'];
                        // It can be an instance
                        if ($filterName instanceof SearchFilter) {
                            $filterName = get_class($filterName);
                        }
                        // It can be a fully qualified class name
                        if (strpos($filterName, '\\') !== false) {
                            $filterNameParts = explode("\\", $filterName);
                            // We expect an alias matching the class name without namespace, see #coresearchaliases
                            $filterName = array_pop($filterNameParts);
                        }
                        $filter = preg_replace('/Filter$/', '', $filterName);
                    }
                    $fields[] = "{$name}:{$filter}";
                } else {
                    $fields[] = $name;
                }
            }
        }
        if (is_null($fields)) {
            if ($obj->hasDatabaseField('Title')) {
                $fields = ['Title'];
            } elseif ($obj->hasDatabaseField('Name')) {
                $fields = ['Name'];
            }
        }

        return $fields;
    }

    /**
     * If an object ID is set, add the object to the list
     *
     * @param GridField $gridField
     * @param SS_List $dataList
     * @return SS_List
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        $objectID = $gridField->State->GridFieldAddRelation(null);
        if (empty($objectID)) {
            return $dataList;
        }
        $object = DataObject::get_by_id($gridField->getModelClass(), $objectID);
        if ($object) {
            // Extra arguments are only supported for ManyManyList
            if ($dataList instanceof ManyManyList) {
                $dataList->add($object, $this->extraFields);
            } else {
                $dataList->add($object);
            }
        }
        $gridField->State->GridFieldAddRelation = null;
        return $dataList;
    }

    /**
     * Get the value of extraFields
     * @return array
     */
    public function getExtraFields()
    {
        return $this->extraFields;
    }

    /**
     * Set the value of extraFields
     *
     * @param array $extraFields
     * @return $this
     */
    public function setExtraFields(array $extraFields)
    {
        $this->extraFields = $extraFields;
        return $this;
    }

    /**
     * Get the value of defaultSearchFilter
     * @return string
     */
    public function getDefaultSearchFilter()
    {
        return $this->defaultSearchFilter;
    }

    /**
     * Set the value of defaultSearchFilter
     *
     * @param string $defaultSearchFilter Replace with PartialMatch for example
     * @return $this
     */
    public function setDefaultSearchFilter($defaultSearchFilter)
    {
        $this->defaultSearchFilter = $defaultSearchFilter;
        return $this;
    }

    /**
     * Get the value of wildcardMatch
     * @return bool
     */
    public function getWildcardMatch()
    {
        return $this->wildcardMatch;
    }

    /**
     * Set the value of wildcardMatch
     *
     * @param bool $wildcardMatch
     * @return $this
     */
    public function setWildcardMatch(bool $wildcardMatch)
    {
        $this->wildcardMatch = $wildcardMatch;
        return $this;
    }
}
