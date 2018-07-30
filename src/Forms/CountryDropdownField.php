<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\DropdownField;
use LeKoala\Base\Geo\CountriesList;

class CountryDropdownField extends DropdownField
{
    protected $hasEmptyDefault = true;

    public function __construct($name = 'CountryCode', $title = null, $source = array(), $value = null)
    {
        if ($title === null) {
            $title = _t('CountryDropdownField.TITLE', 'Country');
        }
        if (empty($source)) {
            $source = CountriesList::get();
        }
        parent::__construct($name, $title, $source, $value);
    }
}
