<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HeaderField;

/**
 * Mimics V3 usage (simply pass the first argument as title and name will be set accordingly)
 */
class SimpleHeaderField extends HeaderField
{
    /**
     * @param string $name
     * @param string $title
     * @param int $headingLevel
     */
    public function __construct($name, $title = null, $headingLevel = 2)
    {
        if ($title === null) {
            $title = FormField::name_to_label($name);
        }
        $name = str_replace(' ', '', $name);
        parent::__construct($name, $title, $headingLevel);
    }
}
