<?php

namespace LeKoala\Base\Logs;

use Monolog\Handler\StreamHandler;

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
