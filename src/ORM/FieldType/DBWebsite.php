<?php

namespace LeKoala\Base\ORM\FieldType;

use LeKoala\Base\Forms\InputMaskUrlField;
use SilverStripe\ORM\FieldType\DBVarchar;

/**
 * Website field type
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
        $input = $this->value;
        $urlParts = parse_url($input);
        // remove www
        if ($removeWWW) {
            $domain = preg_replace('/^www\./', '', $urlParts['host']);
        }
        return $domain;
    }
}
