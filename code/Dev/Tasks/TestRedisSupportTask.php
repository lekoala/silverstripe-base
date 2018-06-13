<?php
namespace LeKoala\Base\Dev\Tasks;

use Predis\Client;
use Predis\Command\ServerInfo;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Core\Injector\Injector;
use LeKoala\Base\Cache\RedisCacheFactory;

/**
 */
class TestRedisSupportTask extends BuildTask
{
    protected $description = 'Check if redis is working properly.';
    private static $segment = 'TestRedisSupportTask';

    public function init()
    {
        $predis = new Client('tcp://127.0.0.1:6379');
        $this->message($predis->executeCommand(new ServerInfo));

        $args = [];
        $redisCache = Injector::inst()->createWithArgs(RedisCacheFactory::class, $args);
        $this->message($redisCache);
    }
}
