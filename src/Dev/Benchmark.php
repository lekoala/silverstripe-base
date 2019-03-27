<?php

namespace LeKoala\Base\Dev;

use LeKoala\Base\Controllers\HasLogger;

class Benchmark
{
    use HasLogger;

    /**
     * A dead simple benchmark function
     *
     * Usage : bm(function() { // Insert here the code to benchmark });
     * Alternative usage : bm() ; // Code to test ; bm();
     *
     * @param callable $cb
     * @return void
     */
    public static function run($cb = null)
    {
        $data = self::benchmark($cb);
        if (!$data) {
            return;
        }

        printf("It took %s seconds and used %s memory", $data['time'], $data['memory']);
        die();
    }

    /**
     * @param callable $cb
     * @return bool|array
     */
    protected static function benchmark($cb = null)
    {
        static $data = null;

        // No callback scenario
        if ($cb === null) {
            if ($data === null) {
                $data = [
                    'startTime' => microtime(true),
                    'startMemory' => memory_get_usage(),
                ];
                // Allow another call
                return false;
            } else {
                $startTime = $data['startTime'];
                $startMemory = $data['startMemory'];

                // Clear for future calls
                $data = null;
            }
        } else {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            $cb();
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $time = sprintf("%.6f", $endTime - $startTime);
        $memory = self::bytesToHuman($endMemory - $startMemory);

        return [
            'time' => $time,
            'memory' => $memory,
        ];
    }

    protected static function bytesToHuman($bytes, $decimals = 2)
    {
        $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    public static function log($name, $cb = null)
    {
        $data = self::benchmark($cb);
        if (!$data) {
            return;
        }

        $time = $data['time'];
        $memory = $data['memory'];

        self::getLogger()->debug("$name : $time seconds and $memory memory.");
    }
}
