<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBVarchar;
use LeKoala\Base\Forms\CountryDropdownField;
use LeKoala\Base\Geo\CountriesList;

/**
 * A country field
 */
class DBCountry extends DBVarchar
{
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, 2, $options);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        $field = CountryDropdownField::create($this->name, $title);
        return $field;
    }

    /**
     * @return string
     */
    public function getCountryName()
    {
        return CountriesList::getNameFromCode($this->value);
    }
}
