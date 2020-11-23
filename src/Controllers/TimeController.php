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
    use HasSession;

    public function index(HTTPRequest $request = null)
    {
        $time = time();
        if (class_exists(\Cake\Chronos\Chronos::class)) {
            // somehow make this configurable
            if ($this->getSession()->get('test_now')) {
                \Cake\Chronos\Chronos::setTestNow($this->getSession()->get('test_now'));
            }
            $time = \Cake\Chronos\Chronos::now()->timestamp;
        }
        return $this->jsonResponse([
            'time' => $time,
            'date' => date('Y-m-d H:i:s', $time),
            'tz' => date_default_timezone_get(),
        ]);
    }
}
