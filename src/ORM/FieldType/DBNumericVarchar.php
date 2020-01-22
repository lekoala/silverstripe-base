<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBVarchar;
use LeKoala\Base\Forms\InputMaskNumericField;
use LeKoala\Base\Helpers\CurrencyFormatter;

/**
 * Store numbers as varchars (to allow nulls for instance) but keep formatting options
 */
class DBNumericVarchar extends DBVarchar
{
    use CurrencyFormatter;

    /**
     * Whole number size
     *
     * @var int
     */
    protected $wholeSize = 9;

    /**
     * Decimal scale
     *
     * @var int
     */
    protected $decimalSize = 2;

    /**
     * Create a new Decimal field.
     *
     * @param string $name
     * @param int $wholeSize
     * @param int $decimalSize
     */
    public function __construct($name = null, $wholeSize = 9, $decimalSize = 2)
    {
        $this->wholeSize = is_int($wholeSize) ? $wholeSize : 9;
        $this->decimalSize = is_int($decimalSize) ? $decimalSize : 2;

        $this->size = $this->wholeSize + ($this->wholeSize % 3) + $this->decimalSize;

        parent::__construct($name);
    }


    public function Nice()
    {
        if ($this->value == 0) {
            return '';
        }
        return number_format($this->value, $this->decimalSize, $this->getCurrencyDecimalSeparator(), $this->getCurrencyGroupingSeparator());
    }

    /**
     * @param string $title
     * @param array $params
     *
     * @return InputMaskNumericField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = new InputMaskNumericField($this->name, $title);
        $field->setDigits($this->decimalSize);
        if (!$this->decimalSize) {
            $field->setRadixPoint("");
        }
        return $field;
    }
}
