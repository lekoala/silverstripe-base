<?php

namespace LeKoala\Base\Forms;

/**
 * Format urls
 * @deprecated Use form-elements
 */
class InputMaskUrlField extends InputMaskField
{
    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        // The alias doesn't work too well it's missing a :
        $this->setAlias(self::ALIAS_URL);
        // $this->setRegex('(https?|ftp)://.*');
    }

    public function setValue($value, $data = null)
    {
        return parent::setValue($value, $data);
    }
}
