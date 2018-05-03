<?php

namespace LeKoala\Base\Geo\Models;

class Country
{
    protected $code;
    protected $name;

    public function __construct($code = null, $name = null)
    {
        $this->code = $code;
        $this->name = $name;

    }

    public static function create($source)
    {
        if (!is_array($source)) {
            $source = explode(',', $source);
        }
        return new self($source[0], $source[1]);
    }

    /**
     * Get the value of code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set the value of code
     *
     * @return  self
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
