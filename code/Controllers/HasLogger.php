<?php

namespace LeKoala\Base\Controllers;

use Psr\Log\LoggerInterface;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Core\Injector\Injector;

trait HasLogger
{

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return Injector::inst()->get(LoggerInterface::class)->withName(ClassHelper::getClassWithoutNamespace(get_called_class()));
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
