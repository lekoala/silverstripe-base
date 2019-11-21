<?php

namespace LeKoala\Base\Geo\Models;

/**
 * A global coordinates model
 */
class Coordinates
{
    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @param string|float $latitude
     * @param string|float $longitude
     */
    public function __construct($latitude = null, $longitude = null)
    {
        if ($latitude && !is_float($latitude)) {
            $latitude = floatval($latitude);
        }
        if ($longitude && !is_float($longitude)) {
            $longitude = floatval($longitude);
        }
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Create from a given source (array, pairs)
     *
     * Coordinates::create('lat,lon')
     * Coordinates::create('lat','lon')
     * Coordinates::create(['lat','lon'])
     *
     * @param mixed $source
     * @return $this
     */
    public static function create($source, ...$more)
    {
        if (!is_array($source)) {
            $source = explode(',', $source);
        }
        if (!empty($more)) {
            $source = array_merge($source, $more);
        }
        return new self($source[0], $source[1]);
    }

    /**
     * Get the value of latitude
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * Set the value of latitude
     *
     * @return $this
     */
    public function setLatitude(float $latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * Get the value of longitude
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * Set the value of longitude
     *
     * @return $this
     */
    public function setLongitude(float $longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }
}
