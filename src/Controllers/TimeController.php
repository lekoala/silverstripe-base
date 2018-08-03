<?php

namespace LeKoala\Base\Controllers;

use SilverStripe\Control\Controller;

class TimeController extends Controller
{
    use WithJsonResponse;

    public function index()
    {
        return $this->jsonResponse([
            'time' => time(),
            'date' => date('Y-m-d H:i:s'),
            'tz' => date_default_timezone_get(),
        ]);
    }
}
