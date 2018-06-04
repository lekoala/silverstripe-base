<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\SapphireTest;
use LeKoala\Base\Services\Graphloc;
use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;
use LeKoala\Base\Services\GeocodeXyz;

class ServicesTest extends SapphireTest
{
    public function testGraphloc()
    {
        $ip = '189.59.228.17';
        $graphloc = new Graphloc;
        $result = $graphloc->get($ip);

        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'BR');
        $this->assertEquals($result->getCoordinates()->getLatitude(), '-23.5733');
    }

    public function testGeocodeXyz()
    {
        $geocodeXyz = new GeocodeXyz;

        $result = $geocodeXyz->reverseGeocode('41.31900', '2.07465');
        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'ES');
        $this->assertEquals($result->getCoordinates()->getLatitude(), '41.31900');
        $this->assertEquals($result->getPostalCode(), '8820');

        $result = $geocodeXyz->geocode("71, avenue des Champs Élysées, Paris, France");
        $this->assertNotEmpty($result);
        $this->assertEquals($result->getCountry()->getCode(), 'FR');
        $this->assertEquals($result->getPostalCode(), '75008');
    }
}
