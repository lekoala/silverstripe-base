<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\ReadonlyField;

/**
 * Better read only field for numeric fields
 */
class NumericReadonlyField extends ReadonlyField
{
    protected $suffix =  '';

    /**
     * @return mixed|string
     */
    public function Value()
    {
        $value = $this->dataValue();
        if (!$value) {
            $value = 0;
        }
        if ($this->suffix) {
            $value .= $this->suffix;
        }
        return $value;
    }

    /**
     * Get the value of suffix
     * @return mixed
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * Set the value of suffix
     *
     * @param mixed $suffix
     * @return $this
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * This already is a readonly field.
     */
    public function performReadonlyTransformation()
    {
        return clone $this;
    }
}
