<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBPercentage;
use LeKoala\Base\Forms\InputMaskPercentageField;

/**
 * Improve percentage field
 *
 * Keep in mind percentage should be a number between 0 and 1 (or over 1 in case of percentage above 100%)
 */
class DBBetterPercentage extends DBPercentage
{

    /**
     * @param string $title
     * @param array $params
     *
     * @return InputMaskPercentageField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = new InputMaskPercentageField($this->name, $title);
        $field->setIsDecimal(true);
        //TODO : handle % over 100 with a given condition
        return $field;
    }

    /**
     * Returns the number, expressed as a percentage. For example, “36.30”
     */
    public function NiceValue()
    {
        return number_format($this->value * 100, $this->decimalSize - 2);
    }
}
