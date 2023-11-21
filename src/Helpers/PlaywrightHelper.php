<?php

namespace LeKoala\Base\Helpers;

use SilverStripe\Control\Director;

class PlaywrightHelper
{

    public static function isTestRunner()
    {
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            $k = strtolower($k);
            // In global.setup.ts, a test.sqlite is created for the test runner
            if ($k === 'x-playwright' && $v == "test-runner") {
                return true;
            }
        }
        return false;
    }

    public static function isTestSetup()
    {
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            $k = strtolower($k);
            // In global.setup.ts, a test.sqlite is created for the test runner
            if ($k === 'x-playwright' && $v == "test-setup") {
                return true;
            }
        }
        return false;
    }

    public static function init()
    {
        if (!Director::isDev()) {
            return;
        }

        if (self::isTestRunner()) {
            self::setTestDb();
        }
        if (self::isTestSetup()) {
            self::setBaseDb();
        }
    }

    public static function setTestDb()
    {
        global $databaseConfig;
        global $database;
        if (class_exists(\SilverStripe\SQLite\SQLite3Database::class)) {
            $database = "test";
            $databaseConfig['type'] = 'SQLite3Database';
            // This doesn't work, you need to use global $database
            // $databaseConfig['database'] = 'base';
            $databaseConfig['path'] = Director::baseFolder() . '/playwright/.db/';

            // let's make sure we have a test db
            if (!is_file($databaseConfig['path'] . $database . ".sqlite")) {
                copy($databaseConfig['path'] . "base.sqlite", $databaseConfig['path'] . $database . ".sqlite");
            }
        }
    }

    public static function setBaseDb()
    {
        global $databaseConfig;
        global $database;
        if (class_exists(\SilverStripe\SQLite\SQLite3Database::class)) {
            $database = "base";
            $databaseConfig['type'] = 'SQLite3Database';
            // This doesn't work, you need to use global $database
            // $databaseConfig['database'] = 'base';
            $databaseConfig['path'] = Director::baseFolder() . '/playwright/.db/';
        }
    }
}
