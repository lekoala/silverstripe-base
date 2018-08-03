<?php

namespace LeKoala\Base\Geo\Services;

use Exception;
use LeKoala\Base\Geo\Models\Address;
use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;
use SilverStripe\i18n\i18n;
use SilverStripe\Core\Environment;

/**
 * In mapbox, coordinates are reversed! (longitude, latitude)
 *
 * @link https://www.mapbox.com/api-playground
 * @link https://github.com/mapbox/geocoding-example/tree/master/php
 */
class MapBox implements Geocoder
{
    const API_URL = 'https://api.mapbox.com/geocoding/v5/{mode}/{query}.json';
    const MODE_PLACES = 'mapbox.places';
    const MODE_PLACES_PERMANENT = 'mapbox.places-permanent';

    /**
     * @link https://www.mapbox.com/api-documentation/#geocoding
     * @param string $query
     * @param array $params country, proximity, types, autocomplete, bbox, limit, language
     * @return Address
     * @throws Exception when there is a problem with the api, otherwise may return an empty address
     */
    protected function query($query, $params = [])
    {
        $url = self::API_URL;
        $url = str_replace('{mode}', self::MODE_PLACES, $url);
        $url = str_replace('{query}', urlencode($query), $url);

        $defaultParams = [
            // 'language' => $this->getLanguage(i18n::get_locale()),
            'types' => 'address',
            'limit' => 1,
            'access_token' => Environment::getEnv('MAPBOX_API_KEY')
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

        $location = null;
        $countryCode = $countryName = null;
        $lat = $lon = null;

        if (!empty($data['features'])) {
            $feature = $data['features'][0];

            $location = [
                // A string of the house number for the returned address feature
                'streetNumber' => $feature['address'] ?? null,
                'streetName' => $feature['text'],
            ];

            $lat = $feature['geometry']['coordinates'][1];
            $lon = $feature['geometry']['coordinates'][0];
            foreach ($feature['context'] as $context) {
                $parts = explode('.', $context['id']);
                $contextType = $parts[0];
                $contextId = $parts[1];
                if ($contextType == 'postcode') {
                    $location['postalCode'] = $context['text'];
                }
                if ($contextType == 'place') {
                    $location['locality'] = $context['text'];
                }
                if ($contextType == 'country') {
                    $countryCode = $context['short_code'];
                    $countryName = $context['text'];
                }
            }
        }

        $country = new Country($countryCode, $countryName);
        $coordinates = new Coordinates($lat, $lon);

        return new Address($location, $country, $coordinates);
    }

    protected function getLanguage($locale)
    {
        $lang = substr($locale, 0, 2);
        if (in_array($lang, self::listLanguages())) {
            return $lang;
        }
        return 'en';
    }

    /**
     * @return array
     */
    public static function listLanguages()
    {
        return [
            'en',
            'de',
            'fr',
            'it',
            'nl',
        ];
    }

    /**
     * @inheritDoc
     */
    public function reverseGeocode($lat, $lon, $params = [])
    {
        return $this->query("$lon,$lat", $params);
    }

    /**
     * @inheritDoc
     */
    public function geocode($address, $params = [])
    {
        return $this->query($address, $params);
    }
}
