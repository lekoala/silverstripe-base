<?php

use SilverStripe\Control\Director;
use SilverStripe\SQLite\SQLite3Database;
use SilverStripe\ORM\Search\FulltextSearchable;

if (!function_exists('bm')) {
    /**
     * A dead simple benchmark function
     * Usage : bm(function() { // Insert here the code to benchmark });
     * Alternative usage : bm() ; // Code to test ; bm();
     */
    function bm($cb = null)
    {
        static $data = null;

        if ($cb === null) {
            if ($data === null) {
                $data = [
                    'startTime' => microtime(true),
                    'startMemory' => memory_get_usage(),
                ];
            // Allow another call
                return;
            }
            else {
                $startTime = $data['startTime'];
                $startMemory = $data['startMemory'];
            }
        }
        else {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            $cb();
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $time = sprintf("%.6f seconds", $endTime - $startTime);

        $bytes = $endMemory - $startMemory;
        $decimals = 2;
        $sz = 'BKMGTP';
        $factor = floor( (strlen($bytes) - 1) / 3);
        $memory = sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];

        printf("It took %s seconds and used %s", $time, $memory);
        die();

    };
}

if(Director::isDev()) {
    error_reporting(-1);
    ini_set('display_errors',true);
}

// When running tests, use SQLite3
// @link https://docs.silverstripe.org/en/4/developer_guides/testing/
if(Director::is_cli()) {
    if(strpos($_SERVER['PHP_SELF'], 'phpunit/') !== false) {
        global $databaseConfig;
        if(class_exists(SQLite3Database::class)) {
            $databaseConfig['type'] = 'SQLite3Database';
            $databaseConfig['path'] = ':memory:';
        }
    }
}
