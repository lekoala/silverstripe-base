<?php

namespace LeKoala\Base\ORM;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

class QueryHelper
{
    const FIRST = 'first';
    const LAST = 'last';
    const RANDOM = 'random';

    /**
     * @param string $class
     * @param int|string|array<mixed> $idOrWhere
     * @return DataObject
     */
    public static function findOne($class, $idOrWhere)
    {
        if (is_int($idOrWhere)) {
            return $class::get_by_id($class, $idOrWhere);
        }
        /** @var DataList $list */
        $list = $class::get();
        if (is_string($idOrWhere)) {
            switch ($idOrWhere) {
                case self::FIRST:
                    return $list->first();
                case self::LAST:
                    return $list->last();
                case self::RANDOM:
                    return $list->orderBy('RAND()')->first();
                default:
                    return $class::get_one($class, $idOrWhere);
            }
        }
        if (is_array($idOrWhere)) {
            return $list->filter($idOrWhere)->first();
        }
    }

    /**
     * @param string $class
     * @param array<mixed> $filters
     * @return DataList
     */
    public static function find($class, $filters = null)
    {
        $list = $class::get();
        if ($filters) {
            $list = $list->filter($filters);
        }
        return $list;
    }
}
