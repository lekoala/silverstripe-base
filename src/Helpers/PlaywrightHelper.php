<?php

namespace LeKoala\Base\Helpers;

use SilverStripe\Control\Director;

class PlaywrightHelper
{
    protected static $is_runner = false;
    protected static $is_setup = false;

    public static function isTestRunner()
    {
        if (self::$is_runner) {
            return true;
        }
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            $k = strtolower($k);
            // In global.setup.ts, a test.sqlite is created for the test runner
            if ($k === 'x-playwright' && $v == "test-runner") {
                self::$is_runner = true;
                return true;
            }
        }
        return false;
    }

    public static function isTestSetup()
    {
        if (self::$is_setup) {
            return true;
        }
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            $k = strtolower($k);
            // In global.setup.ts, a test.sqlite is created for the test runner
            if ($k === 'x-playwright' && $v == "test-setup") {
                self::$is_setup = true;
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
