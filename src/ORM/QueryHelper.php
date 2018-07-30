<?php

namespace LeKoala\Base\ORM;

class QueryHelper
{
    const FIRST = 'first';
    const LAST = 'last';
    const RANDOM = 'random';

    /**
     * @param string $class
     * @param int|string|array $idOrWhere
     * @return DataObject
     */
    public static function findOne($class, $idOrWhere)
    {
        if (is_int($idOrWhere)) {
            return $class::get_by_id($class, $idOrWhere);
        }
        if (is_string($idOrWhere)) {
            switch ($idOrWhere) {
                case self::FIRST:
                    return $class::get()->first();
                case self::LAST:
                    return $class::get()->last();
                case self::RANDOM:
                    return $class::get()->sort('RAND()')->first();
                default:
                    return $class::get_one($class, $idOrWhere);
            }
        }
        if (is_array($idOrWhere)) {
            return $class::get()->filter($idOrWhere)->first();
        }
    }

    /**
     * @param string $class
     * @param array $filters
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
