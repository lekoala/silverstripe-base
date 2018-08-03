<?php

namespace LeKoala\Base\Geo\Services;

use Exception;
use LeKoala\Base\Geo\Models\Address;
use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;
use SilverStripe\Control\Email\Email;

/**
 * @link https://wiki.openstreetmap.org/wiki/Nominatim
 * @link https://github.com/maxhelias/php-nominatim
 */
class Nominatim implements Geocoder
{
    const API_URL = 'https://nominatim.openstreetmap.org/{service}';
    const SEARCH_SERVICE = 'search';
    const REVERSE_SERVICE = 'reverse';
    const LOOKUP_SERVICE = 'lookup';

    /**
     * @param string $query
     * @param array $params countrycodes
     * @return Address
     * @throws Exception when there is a problem with the api, otherwise may return an empty address
     */
    protected function query($query, $params = [])
    {
        $service = self::SEARCH_SERVICE;
        if (isset($params['service'])) {
            $service = $params['service'];
            unset($params['service']);
        }

        if ($query) {
            $params['q'] = $query;
        }

        $url = self::API_URL;
        $url = str_replace('{service}', $service, $url);

        $defaultParams = [
            'email' => Email::config()->admin_email,
            'limit' => 1,
            'format' => 'jsonv2',
            'addressdetails' => 1
        ];

        $params = array_merge($defaultParams, $params);

        $url .= '?' . http_build_query($params);

        $result = file_get_contents($url);
        if (!$result) {
            throw new Exception("The api returned no result");
        }

        $data = json_decode($result, JSON_OBJECT_AS_ARRAY);

        if (!$data) {
            throw new Exception("Failed to decode api results");
        }

        $location = [];
        $countryCode = $countryName = null;
        $lat = $lon = null;

        // in reverse geocoding, it's only one result
        if (isset($data['place_id'])) {
            $row = $data;
        } else {
            $row = $data[0];
        }

        if (isset($row['address'])) {
            $address = $row['address'];
            $location = [
                'streetName' => $address['road'] ?? $address['building'] ?? null,
                'streetNumber' => $address['house_number'] ?? null,
                'postalCode' => $address['postcode'] ?? null,
                'locality' => $address['city'] ?? null,
            ];
            $countryCode = $address['country_code'] ?? null;
            $countryName = $address['country'] ?? null;
        }
        if (!empty($row['lat'])) {
            $lat = $row['lat'];
            $lon = $row['lon'];
        }

        $country = new Country($countryCode, $countryName);
        $coordinates = new Coordinates($lat, $lon);

        return new Address($location, $country, $coordinates);
    }

    /**
     * @inheritDoc
     */
    public function reverseGeocode($lat, $lon, $params = [])
    {
        $params['service'] = self::REVERSE_SERVICE;
        $params['lat'] = $lat;
        $params['lon'] = $lon;
        return $this->query(null, $params);
    }

    /**
     * @inheritDoc
     */
    public function geocode($address, $params = [])
    {
        return $this->query($address, $params);
    }
}
