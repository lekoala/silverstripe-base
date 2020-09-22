<?php

namespace LeKoala\Base\ORM\Search;

use Exception;
use ReflectionProperty;
use InvalidArgumentException;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Search\SearchContext;
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
     * @var array
     */
    protected $wildcardFilters = [];

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
     * Returns a SQL object representing the search context for the given
     * list of query parameters.
     *
     * @param array $searchParams Map of search criteria, mostly taken from $_REQUEST.
     *  If a filter is applied to a relationship in dot notation,
     *  the parameter name should have the dots replaced with double underscores,
     *  for example "Comments__Name" instead of the filter name "Comments.Name".
     * @param array|bool|string $sort Database column to sort on.
     *  Falls back to {@link DataObject::$default_sort} if not provided.
     * @param array|bool|string $limit
     * @param DataList $existingQuery
     * @return DataList
     * @throws Exception
     */
    public function getQuery($searchParams, $sort = false, $limit = false, $existingQuery = null)
    {
        /** DataList $query */
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

        /** @var DataList $query */
        $query = $query->sort($sort);
        $this->setSearchParams($searchParams);

        $count = count($searchParams);
        $isWildcardSearch = true;
        if (!empty($this->wildcardFilters)) {
            $isWildcardSearch = false;
            foreach ($this->wildcardFilters as $wf) {
                if (isset($searchParams[$wf])) {
                    $isWildcardSearch = true;
                }
            }
        }

        // If we search only one value, assume we do a wildcard match
        if ($count === 1 && $isWildcardSearch) {
            $values = array_values($searchParams);
            $value = $values[0];
            $anyFilter = [];
            $list = $this->filters;
            if (!empty($this->wildcardFilters)) {
                $list = [];
                foreach ($this->wildcardFilters as $wf) {
                    if ($filter = $this->getFilter($wf)) {
                        $list[$wf] = $filter;
                    }
                }
            }
            $parts = explode(" ", $value);
            foreach ($parts as $part) {
                $part = trim($part);
                if (!$part) {
                    continue;
                }
                foreach ($list as $filterName => $filter) {
                    $class = str_replace('Filter', '', ClassHelper::getClassWithoutNamespace(get_class($filter)));
                    $key = $filter->getFullName() . ':' . $class;
                    $anyFilter[$key] = $part;
                }
                $query = $query->filterAny($anyFilter);
            }
        } else {
            foreach ($this->searchParams as $key => $value) {
                $key = str_replace('__', '.', $key);
                if ($filter = $this->getFilter($key)) {
                    $filter->setModel($this->modelClass);
                    $filter->setValue($value);
                    if (!$filter->isEmpty()) {
                        $query = $query->alterDataQuery(array($filter, 'apply'));
                    }
                }
            }

            if ($this->connective != "AND") {
                throw new Exception("SearchContext connective '$this->connective' not supported after ORM-rewrite.");
            }
        }
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
}
