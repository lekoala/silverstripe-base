<?php

namespace LeKoala\Base\Security;

use Closure;
use RuntimeException;

/**
 * Timing attacks are a common threat vector for online services.
 * Using a constant code execution time greatly reduces the risk.
 */
class ConstantTimeOperation
{
    public static int $defaultMs = 500;
    public static int $variableMs = 100;

    private static ?int $startedMs = null;
    private static int $targetMs = 0;

    /**
     * Manually start a timer. Use stop() to sleep if necessary
     *
     * @param int|null $targetMs
     * @return void
     */
    public static function start(?int $targetMs): void
    {
        $targetMs = $targetMs ?? self::$defaultMs;
        self::$targetMs = $targetMs + random_int(-1 * self::$variableMs, self::$variableMs);
        self::$startedMs = floor(microtime(true) * 1000);
    }

    /**
     * Sleep until required started time
     *
     * @return void
     */
    public static function stop(): void
    {
        if (self::$startedMs === null) {
            throw new RuntimeException("Timer was not started");
        }
        $end = floor(microtime(true) * 1000);
        $total = $end - self::$startedMs;
        self::$startedMs = null;
        if ($total < self::$targetMs) {
            $sleepMs = self::$targetMs - $total;
            self::wait($sleepMs);
        }
    }

    /**
     * Execute a function using constant execution time
     *
     * @param Closure $closure
     * @param int|null $targetMs The desired execution time in milliseconds
     * @return mixed
     */
    public static function execute(Closure $closure, ?int $targetMs = null): mixed
    {
        self::start($targetMs);
        $result = $closure();
        self::stop();
        return $result;
    }

    /**
     * Wait for a given number of millseconds
     *
     * @param int $milliseconds
     * @return void
     */
    public static function wait(int $milliseconds = 0): void
    {
        if ($milliseconds === 0) {
            return;
        }
        $seconds = (int) ($milliseconds / 1000);
        $nanoSeconds = ($milliseconds % 1000) * 1000000;
        time_nanosleep($seconds, $nanoSeconds);
    }
}
