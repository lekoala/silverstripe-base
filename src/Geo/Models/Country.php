<?php

namespace LeKoala\Base\Geo\Models;

/**
 * A global country model
 */
class Country
{
    /**
     * Uppercased country code
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $name;

    public function __construct($code = null, $name = null)
    {
        if ($code) {
            $code = strtoupper($code);
        }
        $this->code = $code;
        $this->name = $name;
    }

    /**
     * Create from a given source (array, pairs)
     *
     * Country::create('be,Belgium')
     * Country::create('be','Belgium')
     * Country::create(['be','Belgium'])
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
     * Get the uppercased country code
     */
    public function getCode() : ? string
    {
        return $this->code;
    }

    /**
     * Set the country code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = strtoupper($code);
        return $this;
    }

    /**
     * Get the name of the country
     */
    public function getName() : ? string
    {
        return $this->name;
    }

    /**
     * Set the name of the country
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
