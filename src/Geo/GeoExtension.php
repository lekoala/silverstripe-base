<?php

namespace LeKoala\Base\Geo;

use SilverStripe\ORM\DataExtension;

/**
 * An extension that make use of our geo tools
 *
 * @property \LeKoala\Base\Geo\Address|\LeKoala\Base\Geo\GeoExtension $owner
 * @property float $Latitude
 * @property float $Longitude
 * @property string $StreetNumber
 * @property string $StreetName
 * @property string $StreetExtended
 * @property string $PostalCode
 * @property string $Locality
 * @property string $CountryCode
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

    /**
     * @param string $code
     * @return bool
     */
    public function IsCountry($code)
    {
        return $this->owner->CountryCode == $code;
    }

    public function getCountryName()
    {
        return  $this->owner->dbObject('CountryCode')->getCountryName();
    }

    /**
     * Get location (number street)
     *
     * @return string
     */
    public function getStreet()
    {
        return trim($this->owner->StreetNumber . ' ' . $this->owner->StreetName);
    }

    /**
     * Get location (city, country)
     *
     * @return string
     */
    public function getLocation()
    {
        return trim($this->owner->PostalCode . ' ' . $this->owner->Locality . ', ' . $this->getCountryName(), ' ,');
    }

    /**
     * Get location (city, country) on multiple lines
     *
     * @return string
     */
    public function getLocationLines()
    {
        return trim($this->owner->PostalCode . ' ' . $this->owner->Locality . "\n" . $this->getCountryName(), ' ,');
    }

    /**
     * Get location (city, country) with br as line return
     *
     * @return string
     */
    public function getLocationBr()
    {
        return trim($this->owner->PostalCode . ' ' . $this->owner->Locality . "<br/>" . $this->getCountryName(), ' ,');
    }

    /**
     * Full address on 1 line
     *
     * @return string
     */
    public function getAddress()
    {
        return trim($this->getStreet() . ', ' . $this->getLocation());
    }

    /**
     * Full address on multiple lines
     *
     * @return string
     */
    public function getAddressLines()
    {
        return trim($this->getStreet() . "\n" . $this->getLocationLines());
    }

    /**
     * Full address with br as line return
     *
     * @return string
     */
    public function getAddressBr()
    {
        return trim($this->getStreet() . "<br/>" . $this->getLocationBr());
    }
}
