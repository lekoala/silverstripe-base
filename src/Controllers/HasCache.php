<?php

namespace LeKoala\Base\Controllers;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * A trait that adds a static getCache method to access app cache. We use static because we don't now if we have a context
 * and we don't use instance anyway
 * @link https://docs.silverstripe.org/en/4/developer_guides/performance/caching/
 */
trait HasCache
{
    /**
     * @return CacheInterface
     */
    public static function getCache($name = 'app')
    {
        return Injector::inst()->get(CacheInterface::class . '.' . $name);
    }
}
