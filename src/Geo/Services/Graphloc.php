<?php

namespace LeKoala\Base\Geo\Services;

use Exception;
use LeKoala\Base\Geo\Models\Address;
use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;

/**
 * @link https://graphloc.com/
 */
class Graphloc implements Geolocator
{
    const API_URL = 'https://api.graphloc.com/graphql';

    /**
     * @param string $ip
     * @param array $params
     * @return Address
     * @throws Exception
     */
    public function geolocate($ip, $params = [])
    {
        $query = <<<GRAPHQL
{
    getLocation(ip: "$ip") {
        country {
            names {
                en
            }
            iso_code
        }
        city {
            names {
                en
            }
        }
        postal {
            code
        }
        location {
            latitude
            longitude
        }
    }
}
GRAPHQL;
        $variables = [];

        $data = http_build_query(
            array(
                'query' => $query,
                'variables' => $variables,
            )
        );

        $opts = array('http' =>
            array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => $data
        ));

        $context = stream_context_create($opts);

        $url = self::API_URL;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $result = file_get_contents($url, false, $context);
        if (!$result) {
            throw new Exception("The api returned no result");
        }

        $data = json_decode($result, JSON_OBJECT_AS_ARRAY);

        if (!$data) {
            throw new Exception("Failed to decode api results");
        }

        $getLocation = $data['data']['getLocation'];

        $country = new Country($getLocation['country']['iso_code'], $getLocation['country']['names']['en']);
        $coordinates = new Coordinates($getLocation['location']['latitude'], $getLocation['location']['longitude']);

        $addressData = [
            'postalCode' => $getLocation['postal']['code'],
            'locality' => $getLocation['city']['names']['en'],
        ];

        return new Address($addressData, $country, $coordinates);
    }
}
