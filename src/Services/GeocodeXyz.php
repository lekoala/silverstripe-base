<?php

namespace LeKoala\Base\Services;

use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;
use LeKoala\Base\Geo\Models\Address;

/**
 * @link https://geocode.xyz
 */
class GeocodeXyz
{
    const API_URL = 'https://geocode.xyz/{query}?json=1 ';

    /**
     * @param string $query
     * @return Address
     */
    protected function query($query)
    {
        $url = str_replace('{query}', urlencode($query), self::API_URL);
        $result = file_get_contents($url);
        if (!$result) {
            throw new Exception("The api returned no result");
        }

        $data = json_decode($result, JSON_OBJECT_AS_ARRAY);

        if (!$data) {
            throw new Exception("Failed to decode api results");
        }

        $location = null;
        $countryCode = null;
        $coountryName = null;
        if (isset($data['standard'])) {
            $location = [
                'streetName' => $data['standard']['addresst'] ?? null,
                'streetNumber' => $data['standard']['stno'] ?? null,
                'postalCode' => $data['standard']['postal'] ?? null,
                'locality' => $data['standard']['city'] ?? null,
                'country' => $data['standard']['prov'] ?? null,
            ];
            $countryCode =  $data['standard']['prov'] ?? null;
            $coountryName =  $data['standard']['countryname'] ?? null;
        } elseif (isset($data['staddress'])) {
            $location = [
                'streetName' => $data['staddress'] ?? null,
                'streetNumber' => $data['stnumber'] ?? null,
                'postalCode' => $data['postal'] ?? null,
                'locality' => $data['city'] ?? null,
                'country' => $data['prov'] ?? null,
            ];
            $countryCode =  $data['prov'] ?? null;
            $coountryName =  $data['country'] ?? null;
        }

        $country = new Country($countryCode, $coountryName);
        $coordinates = new Coordinates($data['latt'], $data['longt']);

        return new Address($location, $country, $coordinates);

        return $data;
    }

    /**
     * @param string $lat
     * @param string $lng
     * @return Address
     */
    public function reverseGeocode($lat, $lng)
    {
        return $this->query("$lat,$lng");
    }

    /**
     * @param string $address
     * @return Address
     */
    public function geocode($address)
    {
        return $this->query($address);
    }
}
