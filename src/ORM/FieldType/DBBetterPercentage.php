<?php

namespace LeKoala\Base\ORM\FieldType;

use LeKoala\FormElements\InputMaskPercentageField;
use SilverStripe\ORM\FieldType\DBDecimal;
use SilverStripe\ORM\FieldType\DBPercentage;

/**
 * Improve percentage field
 *
 * Keep in mind percentage should be a number between 0 and 1 (or over 1 in case of percentage above 100%)
 *
 * You need to enable allowRelative for percentage above 100%
 */
class DBBetterPercentage extends DBPercentage
{
    protected $allowRelative = false;

    /**
     * Create a new Decimal field.
     *
     * @param string $name
     * @param int $precision
     */
    public function __construct($name = null, $precision = 4, $allowRelative = false)
    {
        if (!$precision) {
            $precision = 4;
        }
        $this->allowRelative = $allowRelative;

        parent::__construct($name, $precision + 1, $precision);
    }

    /**
     * @param string $title
     * @param array $params
     *
     * @return InputMaskPercentageField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = new InputMaskPercentageField($this->name, $title);
        // In SilverStripe, percentage are stored like 1% = 0.01
        $field->setIsDecimal(true);
        return $field;
    }

    /**
     * Returns the number, expressed as a percentage. For example, “36.30”
     */
    public function NiceValue()
    {
        return number_format($this->value * 100, $this->decimalSize - 2);
    }

    public function saveInto($dataObject)
    {
        DBDecimal::saveInto($dataObject);

        if (!$this->allowRelative) {
            $fieldName = $this->name;
            if ($fieldName && $dataObject->$fieldName > 1.0) {
                $dataObject->$fieldName = 1.0;
            }
        }
    }
}
