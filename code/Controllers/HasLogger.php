<?php

namespace LeKoala\Base\Controllers;

use Psr\Log\LoggerInterface;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Core\Injector\Injector;

/**
 * A trait that adds two methods
 * - a getLogger method that gets a Logger with a name based on the current class
 * - a staticLogger method to use in a static context
 */
trait HasLogger
{

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return self::staticLogger();
    }

    /**
     * a static proxy
     * @return LoggerInterface
     */
    public static function staticLogger()
    {
        return Injector::inst()->get(LoggerInterface::class)->withName(ClassHelper::getClassWithoutNamespace(get_called_class()));
    }
}
