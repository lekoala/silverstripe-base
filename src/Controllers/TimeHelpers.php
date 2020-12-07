<?php

namespace LeKoala\Base\Controllers;

use Cake\Chronos\Chronos;

trait TimeHelpers
{
    /**
     * @return string
     */
    public function CurrentDate()
    {
        if (class_exists(\Cake\Chronos\Chronos::class)) {
            return Chronos::now()->toDateTimeString();
        }
        return date('Y-m-d H:i:s');
    }

    /**
     * @return int
     */
    public function CurrentTime()
    {
        if (class_exists(\Cake\Chronos\Chronos::class)) {
            return Chronos::now()->timestamp;
        }
        return time();
    }
}
