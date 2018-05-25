<?php

namespace LeKoala\Base\Test;

use Psr\Log\LoggerInterface;
use SilverStripe\Dev\SapphireTest;
use LeKoala\Base\ContentController;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;

class BaseTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     * @var string
     */
    protected static $fixture_file = 'BaseTest.yml';

    public function testDependencies()
    {
        $inst = ContentController::create();

        $this->assertTrue($inst->getLogger() instanceof LoggerInterface);
        $this->assertTrue($inst->getCache() instanceof CacheInterface);
    }

    public function testRequirements()
    {
        $inst = ContentController::create();

        $SiteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $inst->doInit();

        $backend = Requirements::backend();

        $css = $backend->getCSS();

        $this->assertArrayHasKey("https://fonts.googleapis.com/css?family=Open+Sans", $css);
    }
}
