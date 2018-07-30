<?php

namespace LeKoala\Base\Cache;

use Predis\Client;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Cache\CacheFactory;
use Symfony\Component\Cache\Simple\RedisCache;

/**
 * For windows support, see here
 *
 * @link https://github.com/MicrosoftArchive/redis/releases
 *
 * Predis is the preferred client
 *
 * @link https://github.com/nrk/predis
 */
class RedisCacheFactory implements CacheFactory
{

    /**
     * @var RedisAdapter
     */
    protected $redisClient;

    /**
     * @param RedisAdapter $redisClient
     */
    public function __construct(Client $redisClient = null)
    {
        $this->redisClient = $redisClient;
    }

    /**
     * @inheritdoc
     */
    public function create($service, array $params = array())
    {
        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);

        $defaultLifetime = isset($params['defaultLifetime']) ? $params['defaultLifetime'] : 0;

        return Injector::inst()->createWithArgs(RedisCache::class, [
            $this->redisClient,
            $namespace,
            $defaultLifetime
        ]);
    }
}
