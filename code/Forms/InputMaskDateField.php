<?php
namespace LeKoala\Base\Forms;

/**
 * Format date field using ISO value
 *
 * Locale conversion cannot be done by InputMask and should be provided by a third party service
 */
class InputMaskDateField extends InputMaskField
{
    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);

        $this->setAlias('yyyy-mm-dd');
        $this->setDataFormat('yyyy-mm-dd'); // use ISO date format when unmasking
    }

    public function setValue($value, $data = null) {
        return parent::setValue($value, $data);
    }
}
