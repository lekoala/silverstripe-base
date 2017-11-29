<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use LeKoala\Base\ContentController;
use SilverStripe\View\Requirements;
use SilverStripe\SiteConfig\SiteConfig;

class ContentControllerTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     * @var string
     */
    protected static $fixture_file = 'ContentControllerTest.yml';

    public function testDependencies()
    {
        $inst = ContentController::create();

        $this->assertInstanceOf(Psr\Log\LoggerInterface::class, $inst->getLogger());
        $this->assertInstanceOf(Psr\SimpleCache\CacheInterface::class, $inst->getCache());
    }

    public function testRequirements() {
        $inst = ContentController::create();

        $SiteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $inst->doInit();

        $backend = Requirements::backend();

        $css = $backend->getCSS();

        $this->assertArrayHasKey("https://fonts.googleapis.com/css?family=Open+Sans", $css);
    }
}
