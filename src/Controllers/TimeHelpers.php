<?php

namespace LeKoala\Base\Controllers;

trait TimeHelpers
{
    /**
     * @return string
     */
    public function CurrentDate()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * @return int
     */
    public function CurrentTime()
    {
        return time();
    }
}
