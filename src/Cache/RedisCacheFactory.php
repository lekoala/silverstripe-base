<?php

namespace LeKoala\Base\Cache;

use Predis\Client;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFactory;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

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
    public function create(string $service, array $params = []): CacheInterface
    {
        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);

        $defaultLifetime = isset($params['defaultLifetime']) ? $params['defaultLifetime'] : 0;

        return new Psr16Cache(new RedisAdapter($this->redisClient ?? new Client(), $namespace, $defaultLifetime));
    }
}
