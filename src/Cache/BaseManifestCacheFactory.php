<?php

namespace LeKoala\Base\Cache;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Cache\ManifestCacheFactory;

class BaseManifestCacheFactory extends ManifestCacheFactory
{
    public function __construct(array $args = [], LoggerInterface $logger = null)
    {
        if (!$logger) {
            $stream = Director::baseFolder() . '/manifestcache.log';
            $logger = new Logger("manifestcache-log");
            $logger->pushHandler(new StreamHandler($stream));
        }

        parent::__construct($args, $logger);
    }
}
