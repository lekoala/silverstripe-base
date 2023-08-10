<?php

namespace LeKoala\Base\Logs;

use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Handler\StreamHandler;

if (Logger::API < 3) {
    class SafeStreamHandler extends StreamHandler
    {
        protected function write(array $record)
        {
            try {
                parent::write($record);
            } catch (\UnexpectedValueException $e) {
                // Ignore
            }
        }
    }
} else {
    // Since v3, it uses a LogRecord class
    class SafeStreamHandler extends StreamHandler
    {
        protected function write(LogRecord $record): void
        {
            try {
                parent::write($record);
            } catch (\UnexpectedValueException $e) {
                // Ignore
            }
        }
    }
}
