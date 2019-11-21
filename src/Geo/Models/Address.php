<?php

namespace LeKoala\Base\Geo\Models;

use LeKoala\Base\Geo\Models\Country;
use LeKoala\Base\Geo\Models\Coordinates;

/**
 * A global address model
 */
class Address
{
    /**
     * @var string
     */
    protected $streetName;
    /**
     * @var string
     */
    protected $streetNumber;
    /**
     * @var string
     */
    protected $postalCode;
    /**
     * @var string
     */
    protected $locality;
    /**
     * @var Country
     */
    protected $country;
    /**
     * @var Coordinates
     */
    protected $coordinates;

    /**
     * @param mixed $address
     * @param mixed $country
     * @param mixed $coordinates
     */
    public function __construct($address = null, $country = null, $coordinates = null)
    {
        if ($address !== null) {
            if (is_array($address)) {
                foreach ($address as $k => $v) {
                    if (property_exists($this, $k)) {
                        $this->$k = $v;
                    }
                }
            } else {
                // TODO: parse string address
            }
        }

        if ($country !== null) {
            if (!$country instanceof Country) {
                $country = Country::create($country);
            }
            $this->country = $country;
        } else {
            $this->country = new Country;
        }

        if ($coordinates !== null) {
            if (!$coordinates instanceof Coordinates) {
                $coordinates = Coordinates::create($coordinates);
            }
            $this->coordinates = $coordinates;
        } else {
            $this->coordinates = new Coordinates;
        }
    }


    /**
     * Get the value of country
     *
     * @return Country
     */
    public function getCountry(): Country
    {
        return $this->country;
    }

    /**
     * Set the value of country
     *
     * @param  Country  $country
     *
     * @return $this
     */
    public function setCountry(Country $country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * Get the value of coordinates
     *
     * @return Coordinates
     */
    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }

    /**
     * Set the value of coordinates
     *
     * @param  Coordinates  $coordinates
     *
     * @return $this
     */
    public function setCoordinates(Coordinates $coordinates)
    {
        $this->coordinates = $coordinates;
        return $this;
    }

    /**
     * Get the value of streetName
     *
     * @return string
     */
    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    /**
     * Set the value of streetName
     *
     * @param string $streetName
     *
     * @return $this
     */
    public function setStreetName(string $streetName)
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * Get the value of streetNumber
     *
     * @return string
     */
    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    /**
     * Set the value of streetNumber
     *
     * @param string $streetNumber
     *
     * @return $this
     */
    public function setStreetNumber(string $streetNumber)
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * Get the value of postalCode
     *
     * @return string
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * Set the value of postalCode
     *
     * @param string $postalCode
     *
     * @return $this
     */
    public function setPostalCode(string $postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get the value of locality
     *
     * @return string
     */
    public function getLocality(): ?string
    {
        return $this->locality;
    }

    /**
     * Set the value of locality
     *
     * @param string $locality
     * @return $this
     */
    public function setLocality(string $locality)
    {
        $this->locality = $locality;
        return $this;
    }
}
