<?php

namespace LeKoala\Base\Dev;

class Benchmark
{

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
        static $data = null;

        // No callback scenario
        if ($cb === null) {
            if ($data === null) {
                $data = [
                    'startTime' => microtime(true),
                    'startMemory' => memory_get_usage(),
                ];
                // Allow another call
                return;
            } else {
                $startTime = $data['startTime'];
                $startMemory = $data['startMemory'];
            }
        } else {
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
        $factor = floor((strlen($bytes) - 1) / 3);
        $memory = sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];

        printf("It took %s seconds and used %s memory", $time, $memory);
        die();
    }
}
