<?php

namespace LeKoala\Base\Geo\Services;

use Exception;
use LeKoala\Base\Geo\Models\Address;

interface Geocoder
{
    /**
     * @param string $lat
     * @param string $lng
     * @param array $params
     * @return Address
     * @throws Exception
     */
    public function reverseGeocode($lat, $lng, $params = []);

    /**
     * @param string $address
     * @param array $params
     * @return Address
     * @throws Exception
     */
    public function geocode($address, $params = []);
}
