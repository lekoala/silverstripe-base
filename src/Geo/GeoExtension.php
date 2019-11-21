<?php

namespace LeKoala\Base\Geo;

use SilverStripe\ORM\DataExtension;

/**
 * An extension that make use of our geo tools
 */
class GeoExtension extends DataExtension
{
    private static $db = array(
        // Coordinates
        'Latitude' => 'Float(10,6)',
        'Longitude' => 'Float(10,6)',
        // Address
        'StreetNumber' => 'Varchar(50)',
        'StreetName' => 'Varchar(255)',
        'StreetExtended' => 'Varchar(255)',
        'PostalCode' => 'Varchar(32)',
        'Locality' => 'Varchar(255)',
        'CountryCode' => 'Country',
    );

    /**
     * @var boolean
     */
    public static $disable_auto_geocode = false;

    /**
     * @link http://microformats.org/wiki/adr
     * @return string
     */
    public function getHTMLAddress()
    {
        $html = '';
        $html .= '<div class="adr">';
        $html .= '<div class="street-address">' . $this->owner->StreetNumber . ' ' . $this->owner->StreetName . '</div>';
        if ($this->owner->StreetExtended) {
            $html .= '<div class="extended-address">' . $this->owner->StreetExtended . '</div>';
        }
        $html .= '<span class="locality">' . $this->owner->Locality . '</span>,';
        $html .= '<span class="postal-code">' . $this->owner->PostalCode . '</span>';
        $html .= '<div class="country-name">' . $this->getCountryName() . '</div>';
        $html .= '</div>';

        return $html;
    }

    public function getCountryName()
    {
        return  $this->owner->dbObject('CountryCode')->getCountryName();
    }
}
