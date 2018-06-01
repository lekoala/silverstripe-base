<?php

namespace LeKoala\Base\ORM\Filters;

use SilverStripe\ORM\DB;
use InvalidArgumentException;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\SearchFilter;

/**
 * Matches textual content with a Regexp construct.
 */
class RegexpFilter extends SearchFilter
{
    public function getSupportedModifiers()
    {
        return ['not'];
    }

    /**
     * Apply the match filter to the given variable value
     *
     * @param string $value The raw value
     * @return string
     */
    protected function getMatchPattern($value)
    {
        $value = preg_quote($value);
        return $value;
    }

    /**
     * Apply filter criteria to a SQL query.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        if ($this->aggregate) {
            throw new InvalidArgumentException(sprintf(
                'Aggregate functions can only be used with comparison filters. See %s',
                $this->fullName
            ));
        }

        return parent::apply($query);
    }

    protected function applyOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);

        $whereClause =  $this->getName() . " REGEXP '";
        $whereClause .= $this->getMatchPattern($this->getValue());
        $whereClause .= "'";

        return $this->aggregate ?
            $this->applyAggregate($query, $whereClause) :
            $query->where($whereClause);
    }

    protected function applyMany(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $whereClause =  $this->getName() . " REGEXP '";
        foreach ($this->getValue() as $value) {
            $whereClause .= $this->getMatchPattern($value) . '|';
        }
        $whereClause = rtrim($whereClause, '|');
        $whereClause .= "'";
        return $query->whereAny($whereClause);
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);

        $whereClause =  $this->getName() . " NOT REGEXP '";
        $whereClause .= $this->getMatchPattern($this->getValue());
        $whereClause .= "'";

        return $this->aggregate ?
            $this->applyAggregate($query, $whereClause) :
            $query->where($whereClause);
    }

    protected function excludeMany(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $whereClause =  $this->getName() . " NOT REGEXP '";
        foreach ($this->getValue() as $value) {
            $whereClause .= $this->getMatchPattern($value) . '|';
        }
        $whereClause = rtrim($whereClause, '|');
        $whereClause .= "'";
        return $query->whereAny($whereClause);
    }

    public function isEmpty()
    {
        return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
    }
}
