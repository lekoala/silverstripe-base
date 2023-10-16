<?php

namespace LeKoala\Base\ORM\FieldType;

use LeKoala\FormElements\InputMaskField;
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
        $field = new InputMaskField($this->name, $title);
        $field->setAlias(InputMaskField::ALIAS_URL);
        return $field;
    }

    /**
     * Get a sweet short url
     *
     * @param boolean $removeWWW
     * @param boolean $removeSlash
     * @return string
     */
    public function ShortUrl($removeWWW = true, $removeSlash = true)
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
        // remove ending /
        if ($removeSlash) {
            $domain = trim($domain, '/');
        }

        return $domain;
    }

    public function FullUrl()
    {
        $domain = $this->value;
        if (strpos($domain, 'http') === false) {
            $domain = 'http://' . $domain;
        }
        return $domain;
    }

    public function SecureFullUrl()
    {
        $domain = $this->value;
        if (strpos($domain, 'http') === false) {
            $domain = 'https://' . $domain;
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
