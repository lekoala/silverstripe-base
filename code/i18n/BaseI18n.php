<?php
namespace LeKoala\Base\i18n;

use SilverStripe\i18n\i18n;

class BaseI18n
{
    const GLOBAL_ENTITY = 'Global';

    public static function globalTranslation($entity)
    {
        $parts = explode('.', $entity);
        if (count($parts) == 1) {
            array_unshift($parts, self::GLOBAL_ENTITY);
        }
        return i18n::_t(implode('.', $parts), $entity);
    }
}
