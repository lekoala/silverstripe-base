<?php

namespace LeKoala\Base\Cache;

use Predis\Client;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Cache\CacheFactory;
use Symfony\Component\Cache\Simple\RedisCache;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\CliDebugView;

/**
 * For windows support, see here
 *
 * @link https://github.com/MicrosoftArchive/redis/releases
 *
 * Predis is the preferred client
 *
 * @link https://github.com/nrk/predis
 *
 * Can be defined in your .env file
 *
 *   SS_MANIFESTCACHE="\LeKoala\Base\Cache\RedisManifestCacheFactory"
 *   SS_MANIFESTCACHE_HOST="tcp://127.0.0.1:6379";
 *
 * Don't forget to properly autoload this class with composer
 *
 *  "autoload": {
 *        "psr-4": {
 *            "LeKoala\\Base\\": "base/src/"
 *        }
 *   },
 *
 * @link https://docs.silverstripe.org/en/4/developer_guides/execution_pipeline/manifests/
 */
class RedisManifestCacheFactory implements CacheFactory
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
        if (!$redisClient) {
            $redisClient = new Client(Environment::getEnv('SS_MANIFESTCACHE_HOST'));
        }
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

        // As we use the manifest to store config, we cannot call it, so don't use injector
        $inst = new RedisCache($this->redisClient, $namespace, $defaultLifetime);
        return $inst;
    }
}
