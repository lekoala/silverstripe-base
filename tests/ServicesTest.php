<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\SapphireTest;
use LeKoala\Base\Services\Graphloc;
use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;

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
}
