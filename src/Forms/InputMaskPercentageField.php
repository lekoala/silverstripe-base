<?php
namespace LeKoala\Base\Forms;

/**
 * Format %
 */
class InputMaskPercentageField extends InputMaskNumericField
{
    /**
     * @boolean
     */
    protected $isDecimal = false;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $this->setAlias(self::ALIAS_PERCENTAGE);
    }

    public function setValue($value, $data = null)
    {
        if ($this->isDecimal) {
            $value = $value * 100;
        }
        return parent::setValue($value, $data);
    }

    public function Field($properties = [])
    {
        if ($this->isDecimal) {
            $this->setAttribute('data-is-decimal', true);
        }
        return parent::Field();
    }

    public function dataValue()
    {
        $value = parent::dataValue();
        if ($this->isDecimal) {
            $value = $value / 100;
        }
        return $value;
    }

    /**
     * Create a new class for this field
     */
    public function performReadonlyTransformation()
    {
        // $field = $this->castedCopy('SilverStripe\\Forms\\NumericField');
        // $field->setReadonly(true);
        $field = $this->castedCopy(NumericReadonlyField::class);
        $field->setSuffix('%');
        return $field;
    }

    /**
     * Get the value of isDecimal
     */
    public function getIsDecimal()
    {
        return $this->isDecimal;
    }

    /**
     * Set the value of isDecimal
     *
     * @return $this
     */
    public function setIsDecimal($isDecimal)
    {
        $this->isDecimal = $isDecimal;
        return $this;
    }
}
