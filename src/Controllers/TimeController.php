<?php

namespace LeKoala\Base\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

/**
 * Class \LeKoala\Base\Controllers\TimeController
 *
 */
class TimeController extends Controller
{
    use WithJsonResponse;

    public function index(HTTPRequest $request = null)
    {
        return $this->jsonResponse([
            'time' => time(),
            'date' => date('Y-m-d H:i:s'),
            'tz' => date_default_timezone_get(),
        ]);
    }
}
