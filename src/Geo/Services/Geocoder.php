<?php

namespace LeKoala\Base\Geo\Services;

use Exception;
use LeKoala\Base\Geo\Models\Address;

interface Geocoder
{
    /**
     * @param string $lat
     * @param string $lon
     * @param array $params
     * @return Address
     * @throws Exception
     */
    public function reverseGeocode($lat, $lon, $params = []);

    /**
     * @param string $address
     * @param array $params
     * @return Address
     * @throws Exception
     */
    public function geocode($address, $params = []);
}
