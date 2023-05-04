<?php

namespace LeKoala\Base\Controllers;

trait TimeHelpers
{
    /**
     * @return string
     */
    public function CurrentDate()
    {
        if (class_exists(\Carbon\Carbon::class)) {
            return \Carbon\Carbon::now()->toDateTimeString();
        }
        if (class_exists(\Cake\Chronos\Chronos::class)) {
            return \Cake\Chronos\Chronos::now()->toDateTimeString();
        }
        return date('Y-m-d H:i:s');
    }

    /**
     * @return int
     */
    public function CurrentTime()
    {
        if (class_exists(\Carbon\Carbon::class)) {
            return \Carbon\Carbon::now()->timestamp;
        }
        if (class_exists(\Cake\Chronos\Chronos::class)) {
            return \Cake\Chronos\Chronos::now()->timestamp;
        }
        return time();
    }
}
