<?php

namespace LeKoala\Base\ORM\FieldType;

use LeKoala\Base\Forms\InputMaskUrlField;
use SilverStripe\ORM\FieldType\DBVarchar;

/**
 * Website field type
 *
 * Allows better back end functionnalities (with input mask)
 * and front end use with $Website.Nice to display user friendly urls
 */
class DBWebsite extends DBVarchar
{

    public function scaffoldFormField($title = null, $params = null)
    {
        $field = InputMaskUrlField::create($this->name, $title);
        return $field;
    }

    /**
     * Get a sweet short url
     *
     * @param boolean $removeWWW
     * @return string
     */
    public function ShortUrl($removeWWW = true)
    {
        $domain = $input = $this->value;
        $urlParts = parse_url($input);
        if (isset($urlParts['host'])) {
            $domain = $urlParts['host'];
        }
        // remove www
        if ($removeWWW) {
            $domain = preg_replace('/^www\./', '', $domain);
        }
        return $domain;
    }

    /**
     * Alias for typical SilverStripe conventions
     *
     * @return string
     */
    public function Nice()
    {
        return $this->ShortUrl();
    }
}
