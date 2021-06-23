<?php

namespace LeKoala\Base\Forms\GridField;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * see https://github.com/silverstripe/silverstripe-framework/pull/9991
 */
class BetterGridFieldAddExistingAutocompleter extends GridFieldAddExistingAutocompleter
{
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
}
