<?php

namespace LeKoala\Base\Logs;

use Monolog\LogRecord;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use SilverStripe\Control\Director;

class SafeStreamHandler extends StreamHandler
{
    public function __construct($stream, int|string|Level $level = Level::Debug, bool $bubble = true, ?int $filePermission = null, bool $useLocking = false)
    {
        $stream = str_replace('{SS_BASE_FOLDER}', Director::baseFolder(), $stream);
        parent::__construct($stream, $level, $bubble, $filePermission, $useLocking);
    }

    protected function write(LogRecord $record): void
    {
        try {
            parent::write($record);
        } catch (\UnexpectedValueException $e) {
            // Ignore
        }
    }
}
