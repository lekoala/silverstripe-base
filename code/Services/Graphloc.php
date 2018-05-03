<?php

namespace LeKoala\Base\Services;

use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;
use LeKoala\Base\Geo\Models\Address;

/**
 * TODO: implement caching
 * @link https://graphloc.com/
 */
class Graphloc
{
    const API_URL = 'https://api.graphloc.com/graphql';

    /**
     * @param string $ip
     * @return Address
     */
    public function get($ip)
    {
        $query = <<<GRAPHQL
{
    getLocation(ip: "$ip") {
        country {
            names {
                en
            }
            geoname_id
            iso_code
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

        $result = file_get_contents(self::API_URL, false, $context);
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

        return new Address(null, $country, $coordinates);
    }
}
