<?php

namespace LeKoala\Base\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

/**
 * Send the server current time
 * Useful for server based countdown/time counters
 */
class TimeController extends Controller
{
    use WithJsonResponse;

    public function index(HTTPRequest $request = null)
    {
        $time = time();
        if (class_exists(\Cake\Chronos\Chronos::class)) {
            $time = \Cake\Chronos\Chronos::now();
        }
        return $this->jsonResponse([
            'time' => $time,
            'date' => date('Y-m-d H:i:s', $time),
            'tz' => date_default_timezone_get(),
        ]);
    }
}
