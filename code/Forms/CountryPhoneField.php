<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FieldGroup;
use LeKoala\Base\Geo\CountriesList;

/**
 *
 */
class CountryPhoneField extends FieldGroup
{
    public function __construct($name)
    {
        $children = [];

        $source = array_keys(CountriesList::get());
        $source =array_combine($source, $source);
        $country = new CountryDropdownField($name . "[CountryCode]", "", $source);
        $country->setAttribute('size', 1);
        $children[] = $country;

        $number = new PhoneField($name . "[Number]", "");
        $children[] = $number;

        parent::__construct($children);
    }
}
