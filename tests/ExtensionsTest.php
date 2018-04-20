<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\SapphireTest;
use LeKoala\Base\Test\ExtendedModel;
use SilverStripe\Control\Controller;
use LeKoala\Base\Extensions\IPExtension;

class ExtensionsTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     * @var string
     */
    protected static $fixture_file = 'ExtensionsTest.yml';

    protected static $extra_dataobjects = array(
        ExtendedModel::class,
    );

    public function testHasExtensions()
    {
        $model = new ExtendedModel();

        $this->assertTrue($model->hasExtension(IPExtension::class));
    }

    public function testIPExtension()
    {
        $model = new ExtendedModel();
        $model->write();
        $this->assertNotEmpty($model->Ip);

        $ip = '127.0.0.1';
        $model->Ip = $ip;
        $this->assertEquals($ip, $model->Ip);
    }
}
