<?php

namespace LeKoala\Base\Geo\Services;

use Exception;
use LeKoala\Base\Geo\Models\Address;

interface Geolocator
{
    /**
     * @param string $ip
     * @param array $params
     * @return Address
     * @throws Exception
     */
    public function geolocate($ip, $params = []);
}
