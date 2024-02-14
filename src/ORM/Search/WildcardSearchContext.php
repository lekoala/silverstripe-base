<?php

namespace LeKoala\Base\ORM\Search;

use Exception;
use ReflectionProperty;
use InvalidArgumentException;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\Filters\EndsWithFilter;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Filters\StartsWithFilter;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;

/**
 * Allows wildcard search in ModelAdmin
 *
 * Sample usage
 * $filter = GridFieldHelper::getGridFieldFilterHeader($config);
 * $wildCardHeader = WildcardSearchContext::fromContext($filter->getSearchContext($gridfield));
 * $wildCardHeader->setWildcardFilters(['FirstName', 'Surname']);
 * $wildCardHeader->replaceInFilterHeader($filter);
 */
class WildcardSearchContext extends SearchContext
{
    /**
     * @var array<string>
     */
    protected $wildcardFilters = [];

    /**
     * @var string
     */
    protected $defaultFilterClass = null;

    /**
     * @var boolean
     */
    protected $filterPunctation = false;

    /**
     * @var boolean
     */
    protected $expandSpace = true;

    /**
     * Use this to apply manually this new search context
     * instead of the default one
     *
     * @param GridFieldFilterHeader $component
     * @return void
     */
    public function replaceInFilterHeader(GridFieldFilterHeader $component)
    {
        $reflection = new ReflectionProperty(get_class($component), 'searchContext');
        $reflection->setAccessible(true);
        $reflection->setValue($component, $this);
    }

    /**
     * @param GridField $gridField
     * @return void
     */
    public function replaceInGridField(GridField $gridField)
    {
        /** @var GridFieldFilterHeader $component */
        $component = $gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);
        $this->replaceInFilterHeader($component);
    }

    /**
     * Generate an instance of this class using an existing context
     *
     * @param SearchContext $context
     * @return WildcardSearchContext
     */
    public static function fromContext(SearchContext $context)
    {
        $reflection = new ReflectionProperty(get_class($context), 'modelClass');
        $reflection->setAccessible(true);
        $modelClass = $reflection->getValue($context);

        return new static($modelClass, $context->getFields(), $context->getFilters());
    }

    /**
     * @param string $name
     * @return SearchFilter
     */
    public function getRealFilter($name)
    {
        // The filter exist in searchable_fields
        // it will return by default a PartialMatch
        $filter = $this->getFilter($name);
        if (empty($filter)) {
            $filterClass = $this->defaultFilterClass ?? PartialMatchFilter::class;
            $filter = new $filterClass($name);
        }
        return $filter;
    }

    /**
     * @param string $value
     * @return array
     */
    public static function findShortcutInString($value)
    {
        $shortcut = null;
        if (strpos($value, ':') === 1) {
            $parts = explode(":", $value);
            $shortcut = array_shift($parts);
            $value = implode(":", $parts);
        }

        $filterClass = null;
        if ($shortcut) {
            switch ($shortcut) {
                case 's':
                    $filterClass = StartsWithFilter::class;
                    break;
                case 'e':
                    $filterClass = EndsWithFilter::class;
                    break;
                case '=':
                    $filterClass = ExactMatchFilter::class;
                    break;
            }
        }

        return [
            'shortcut' => $shortcut,
            'filterClass' => $filterClass,
            'value' => $value,
        ];
    }

    /**
     * Find out the real filter name
     * @param string|SearchFilter $filterName
     * @return string
     */
    public static function getFilterName($filterName)
    {
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
        // Remove suffix
        $filterName = preg_replace('/Filter$/', '', $filterName);
        $filterName = preg_replace('/SearchFilter$/', '', $filterName);
        return $filterName;
    }

    /**
     * Returns a SQL object representing the search context for the given
     * list of query parameters.
     *
     * @param array $searchParams Map of search criteria, mostly taken from $_REQUEST.
     *  If a filter is applied to a relationship in dot notation,
     *  the parameter name should have the dots replaced with double underscores,
     *  for example "Comments__Name" instead of the filter name "Comments.Name".
     * @param array|bool|string $sort Database column to sort on.
     *  Falls back to {@link DataObject::$default_sort} if not provided.
     * @param ?int|array<string,int|null> $limit
     * @param DataList $existingQuery
     * @return DataList
     * @throws Exception
     */
    public function getQuery($searchParams, $sort = false, $limit = false, $existingQuery = null)
    {
        /** @var DataList $query */
        $query = null;
        if ($existingQuery) {
            if (!($existingQuery instanceof DataList)) {
                throw new InvalidArgumentException("existingQuery must be DataList");
            }
            if ($existingQuery->dataClass() != $this->modelClass) {
                throw new InvalidArgumentException("existingQuery's dataClass is " . $existingQuery->dataClass()
                    . ", $this->modelClass expected.");
            }
            $query = $existingQuery;
        } else {
            $query = DataList::create($this->modelClass);
        }

        if (is_array($limit)) {
            $query = $query->limit(
                isset($limit['limit']) ? $limit['limit'] : null,
                isset($limit['start']) ? $limit['start'] : null
            );
        } else {
            $query = $query->limit($limit);
        }

        /** @var DataObject $obj */
        $obj = singleton($this->modelClass);

        if ($sort) {
            /** @var DataList $query */
            $query = $query->sort($sort);
        }

        $this->setSearchParams($searchParams);

        // If we use specific set of fields, make sure we have a value for them
        if (empty($this->wildcardFilters)) {
            if ($obj->hasField('Title')) {
                $this->wildcardFilters = ['Title'];
            } elseif ($obj->hasField('Name')) {
                $this->wildcardFilters = ['Name'];
            }
        }

        // q holds the wildcard search
        if (!empty($searchParams['q'])) {
            $value = $searchParams['q'];

            // Look for search shortcuts like s: or e:
            $shortcutData = self::findShortcutInString($value);
            $forceFilter = null;
            if ($shortcutData['shortcut']) {
                $value = $shortcutData['value'];
                $forceFilter = $shortcutData['filterClass'];
            }

            $anyFilter = [];
            $list = $this->filters;
            if (!empty($this->wildcardFilters)) {
                $list = [];
                foreach ($this->wildcardFilters as $wf) {
                    if ($forceFilter) {
                        $list[$wf] = new $forceFilter($wf);
                        continue;
                    }
                    $filter = $this->getRealFilter($wf);
                    $list[$wf] = $filter;
                }
            }

            $baseValue = $value;
            if ($this->expandSpace) {
                $value = str_replace(" ", "%", $value);
            }
            if ($this->filterPunctation) {
                $value = str_replace(['.', '_', '-'], ' ', $value);
            }
            $value = trim($value);
            foreach ($list as $filterName => $filter) {
                $class = self::getFilterName($filter);
                $key = $filter->getFullName() . ':' . $class;
                if ($value) {
                    $anyFilter[$key] = $value;
                }
                // also look on unfiltered data
                if ($value != $baseValue) {
                    $anyFilter[$key] = $baseValue;
                }
            }
            $query = $query->filterAny($anyFilter);
        }

        // Apply any other search criteria set through advanced search
        foreach ($this->searchParams as $key => $value) {
            // ignore general search
            if ($key === "q") {
                continue;
            }
            // Don't filter punction in advanced search
            // if ($this->filterPunctation) {
            //     $value = str_replace(['.', '_', '-'], ' ', $value);
            // }
            $key = str_replace('__', '.', $key);
            $filter = $this->getRealFilter($key);
            $filter->setModel($this->modelClass);
            $filter->setValue($value);
            if (!$filter->isEmpty()) {
                $query = $query->alterDataQuery(function (DataQuery $query) use ($filter) {
                    $filter->apply($query);
                });
            }
        }

        // if ($this->connective != "AND") {
        //     throw new Exception("SearchContext connective '$this->connective' not supported after ORM-rewrite.");
        // }

        return $query;
    }

    /**
     * Get the value of wildcardFilters
     * @return array
     */
    public function getWildcardFilters()
    {
        return $this->wildcardFilters;
    }

    /**
     * Set the value of wildcardFilters
     *
     * @param array $wildcardFilters
     * @return $this
     */
    public function setWildcardFilters(array $wildcardFilters)
    {
        $this->wildcardFilters = $wildcardFilters;
        return $this;
    }

    /**
     * Get the value of filterPunctation
     * @return bool
     */
    public function getFilterPunctuation()
    {
        return $this->filterPunctation;
    }

    /**
     * Set the value of filterPunctation
     *
     * @param array $filterPunctation
     * @return $this
     */
    public function setFilterPunctuation($filterPunctation)
    {
        $this->filterPunctation = $filterPunctation;
        return $this;
    }

    /**
     * Get the value of defaultFilterClass
     */
    public function getDefaultFilterClass()
    {
        return $this->defaultFilterClass;
    }

    /**
     * Set the value of defaultFilterClass
     *
     * @param string $defaultFilterClass
     */
    public function setDefaultFilterClass($defaultFilterClass)
    {
        $this->defaultFilterClass = $defaultFilterClass;
        return $this;
    }

    /**
     * Get the value of expandSpace
     */
    public function getExpandSpace()
    {
        return $this->expandSpace;
    }

    /**
     * Set the value of expandSpace
     *
     * @param boolean $expandSpace
     */
    public function setExpandSpace($expandSpace)
    {
        $this->expandSpace = $expandSpace;
        return $this;
    }
}
