<?php

namespace LeKoala\Base\Controllers;

use Psr\Log\LoggerInterface;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Core\Injector\Injector;

/**
 * A trait that adds a static getLogger. We use static because we don't now if we have a context
 * and we don't use instance anyway
 */
trait HasLogger
{
    /**
     * @return LoggerInterface
     */
    public static function getLogger()
    {
        return Injector::inst()->get(LoggerInterface::class)->withName(ClassHelper::getClassWithoutNamespace(get_called_class()));
    }
}
