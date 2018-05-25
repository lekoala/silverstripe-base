<?php

namespace LeKoala\Base\Test;

use SilverStripe\Dev\SapphireTest;
use LeKoala\Base\ORM\FieldType\DBPhone;

class ORMTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     * @var string
     */
    protected static $fixture_file = 'TestModel.yml';

    protected static $extra_dataobjects = array(
        TestModel::class,
    );

    public function testPhoneField()
    {
        $field = new DBPhone('Test');

        $nationalNumber = '0473 123 456';
        $nationalNumberNoSpace = str_replace(' ', '', $nationalNumber);
        $internationalNumber = '+32 473 123 456';
        $internationalNumberNoSpace = str_replace(' ', '', $internationalNumber);
        $region = 'be';
        $otherRegion = 'fr';

        $field->setValue($nationalNumber);
        $this->assertEquals($internationalNumberNoSpace, $field->International());
        $this->assertEquals($nationalNumberNoSpace, $field->National());
        $field->setValue($internationalNumber);
        $this->assertEquals($internationalNumberNoSpace, $field->International());
        $this->assertEquals($nationalNumberNoSpace, $field->National());
    }
}
