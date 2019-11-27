<?php

namespace LeKoala\Base\Geo;

use SilverStripe\ORM\DataObject;

/**
 * Represents address in the database
 *
 * Uses GeoExtension
 *
 * Useful for items with multiple addresses like home, delivery, invoicing or multiple locations
 *
 * @property float $Latitude
 * @property float $Longitude
 * @property string $StreetNumber
 * @property string $StreetName
 * @property string $StreetExtended
 * @property string $PostalCode
 * @property string $Locality
 * @property string $CountryCode
 * @property string $Phone
 * @property string $Email
 * @property string $Notes
 * @mixin \LeKoala\Base\Geo\GeoExtension
 */
class Address extends DataObject
{
    private static $table_name = 'Address'; // When using namespace, specify table name

    private static $db = [
        "Phone" => "Phone", // contact phone...
        "Email" => "Varchar", // contact email in case of notification
        "Notes" => "Text", // delivery notes etc
    ];

    public function getTitle()
    {
        return $this->getAddress();
    }
}
