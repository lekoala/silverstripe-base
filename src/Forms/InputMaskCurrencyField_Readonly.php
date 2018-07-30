<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\ReadonlyField;
use LeKoala\Base\Helpers\CurrencyFormatter;

/**
 * Readonly version of a {@link InputMaskCurrencyField}.
 */
class InputMaskCurrencyField_Readonly extends ReadonlyField
{
    use CurrencyFormatter;

    /**
     * Overloaded to display the correctly formated value for this datatype
     *
     * @param array $properties
     * @return string
     */
    public function Field($properties = array())
    {
        $val = Convert::raw2xml($this->value);
        $val = $this->formattedCurrency($this->value);
        $valforInput = Convert::raw2att($val);

        return "<span class=\"readonly " . $this->extraClass() . "\" id=\"" . $this->ID() . "\">$val</span>"
            . "<input type=\"hidden\" name=\"" . $this->name . "\" value=\"" . $valforInput . "\" />";
    }

    /**
     * This already is a readonly field.
     */
    public function performReadonlyTransformation()
    {
        return clone $this;
    }
}
