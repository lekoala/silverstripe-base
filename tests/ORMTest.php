<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\SapphireTest;
use LeKoala\PhoneNumber\DBPhone;

class ORMTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     * @var string
     */
    protected static $fixture_file = 'Test_BaseModel.yml';

    protected static $extra_dataobjects = array(
        Test_BaseModel::class,
    );

    public function testPhoneField()
    {
        $model = new Test_BaseModel();

        $field = new DBPhone('Phone');

        $nationalNumber = '0473 12 34 56';
        $nationalNumberNoSpace = str_replace(' ', '', $nationalNumber);
        $internationalNumber = '+32 473 12 34 56';
        $internationalNumberNoSpace = str_replace(' ', '', $internationalNumber);
        $region = 'be';
        $otherRegion = 'fr';

        $field->setValue($nationalNumber, $model);
        // $this->assertEquals($internationalNumber, $field->International());
        $this->assertEquals($nationalNumber, $field->National());
        $field->setValue($internationalNumber);
        $this->assertEquals($internationalNumber, $field->International());
        $this->assertEquals($nationalNumber, $field->National());
    }
}
