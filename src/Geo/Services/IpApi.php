<?php

namespace LeKoala\Base\Geo\Services;

use Exception;
use LeKoala\Base\Geo\Models\Address;
use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;

/**
 * @link http://ip-api.com/docs/api:json
 */
class IpApi implements Geolocator
{
    const API_URL = 'http://ip-api.com/json/{ip}';

    /**
     * @param string $ip
     * @param array $params
     * @return Address
     * @throws Exception
     */
    public function geolocate($ip, $params = [])
    {
        $url = str_replace('{ip}', $ip, self::API_URL);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $result = file_get_contents($url);
        if (!$result) {
            throw new Exception("The api returned no result");
        }

        $data = json_decode($result, JSON_OBJECT_AS_ARRAY);

        if (!$data) {
            throw new Exception("Failed to decode api results");
        }

        if ($data['status'] != 'success') {
            throw new Exception("Api returned an error");
        }

        $country = new Country($data['countryCode'], $data['country']);
        $coordinates = new Coordinates($data['lat'], $data['lon']);

        $addressData = [
            'postalCode' => $data['zip'],
            'locality' => $data['city'],
        ];
        return new Address($addressData, $country, $coordinates);
    }
}
