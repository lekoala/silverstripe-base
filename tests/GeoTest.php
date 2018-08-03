<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\SapphireTest;
use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Services\IpApi;
use LeKoala\Base\Geo\Services\Geocoder;
use LeKoala\Base\Geo\Services\Graphloc;
use LeKoala\Base\Geo\Models\Coordinates;
use SilverStripe\Core\Injector\Injector;
use LeKoala\Base\Geo\Services\GeocodeXyz;
use LeKoala\Base\Geo\Services\Geolocator;
use LeKoala\Base\Geo\Services\MapBox;
use SilverStripe\Core\Environment;
use LeKoala\Base\Geo\Services\Nominatim;

class GeoTest extends SapphireTest
{
    public function testInjector()
    {
        $geocoder = Injector::inst()->get(Geocoder::class);
        $this->assertTrue($geocoder instanceof Geocoder);
        $geolocator = Injector::inst()->get(Geolocator::class);
        $this->assertTrue($geolocator instanceof Geolocator);
        // It's a singleton
        $geolocator2 = Injector::inst()->get(Geolocator::class);
        $this->assertSame($geolocator, $geolocator2);
    }

    public function testIpApi()
    {
        $ip = '189.59.228.17';
        $service = new IpApi;
        $result = $service->geolocate($ip);

        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'BR');
        $this->assertEquals($result->getCoordinates()->getLatitude(), '-23.5733');
        $this->assertEquals($result->getPostalCode(), '01323');
    }

    public function testGraphloc()
    {
        $ip = '208.80.152.201';
        $service = new Graphloc;
        $result = $service->geolocate($ip);

        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'US');
        $this->assertEquals($result->getCoordinates()->getLatitude(), '37.7898');
        $this->assertEquals($result->getPostalCode(), '94105');
    }

    public function testGeocodeXyz()
    {
        $service = new GeocodeXyz;

        $result = $service->reverseGeocode('41.31900', '2.07465');
        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'ES');
        $this->assertEquals(round($result->getCoordinates()->getLatitude(), 3), 41.319);
        $this->assertEquals($result->getPostalCode(), '8820');

        // No hammering
        sleep(1);

        $result = $service->geocode("71, avenue des Champs Élysées, Paris, France");
        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'FR');
        $this->assertEquals($result->getPostalCode(), '75008');
        $this->assertEquals(round($result->getCoordinates()->getLatitude(), 3), 48.871);
    }

    public function testMapBox()
    {
        if (!Environment::getEnv('MAPBOX_API_KEY')) {
            $this->markTestSkipped("Need a MAPBOX_API_KEY env");
        }

        $service = new MapBox;

        $result = $service->reverseGeocode('41.31900', '2.07465');
        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'ES');
        $this->assertEquals(round($result->getCoordinates()->getLatitude(), 3), 41.319);
        $this->assertEquals($result->getPostalCode(), '8820');

        // No hammering
        sleep(1);

        $result = $service->geocode("71, avenue des Champs Élysées, Paris, France");
        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'FR');
        $this->assertEquals($result->getPostalCode(), '75008');
        $this->assertEquals(round($result->getCoordinates()->getLatitude(), 3), 48.871);
    }

    public function testNominatim()
    {
        $service = new Nominatim;

        $result = $service->reverseGeocode('41.31900', '2.07465');
        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'ES');
        $this->assertEquals(round($result->getCoordinates()->getLatitude(), 3), 41.319);
        $this->assertEquals($result->getPostalCode(), '8820');

        // No hammering
        sleep(1);

        $result = $service->geocode("71, avenue des Champs Élysées, Paris, France");
        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'FR');
        $this->assertEquals($result->getPostalCode(), '75008');
        $this->assertEquals(round($result->getCoordinates()->getLatitude(), 3), 48.871);
    }
}
