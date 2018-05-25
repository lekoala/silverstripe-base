<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use LeKoala\Base\Extensions\IPExtension;

class ExtensionsTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     * @var string
     */
    protected static $fixture_file = 'TestModel.yml';

    protected static $extra_dataobjects = array(
        TestModel::class,
    );

    public function testHasExtensions()
    {
        $model = new TestModel();

        $this->assertTrue($model->hasExtension(IPExtension::class));
    }

    public function testIPExtension()
    {
        $model = new TestModel();
        $model->write();
        $this->assertNotEmpty($model->Ip);

        $ip = '127.0.0.1';
        $model->Ip = $ip;
        $this->assertEquals($ip, $model->Ip);
    }
}
