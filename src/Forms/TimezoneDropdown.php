<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\ArrayLib;

/**
 * A timezone dropdown
 */
class TimezoneDropdown extends Select2SingleField
{
    /**
     * @param string $name The field name
     * @param string $title The field title
     * @param array|ArrayAccess $source A map of the dropdown items
     * @param mixed $value The current value
     */
    public function __construct($name, $title = null, $source = array(), $value = null)
    {
        if (empty($source)) {
            $source = ArrayLib::valuekey(timezone_identifiers_list());
        }
        parent::__construct($name, $title, $source, $value);
    }
}
