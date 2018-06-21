<?php
namespace LeKoala\Base\Forms;

/**
 * Format currency
 */
class InputMaskCurrencyField extends InputMaskField
{
    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);

        $this->setAlias(self::ALIAS_CURRENCY);
    }

    public function setValue($value, $data = null)
    {
        return parent::setValue($value, $data);
    }
}
