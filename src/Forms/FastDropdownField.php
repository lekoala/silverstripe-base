<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Forms\DropdownField;

/**
 * Useful when rendering lots of dropdown fields on a given page
 * Simply prerender the template and clone this field instance
 *
 * You can then change name, class, id and value
 */
class FastDropdownField extends DropdownField
{
    protected $prerender;
    protected $prerenderAttrs = [];

    public function prerenderTemplate()
    {
        $this->prerenderAttrs = [
            'name' => $this->getName(),
            'class' => $this->extraClass(),
            'id' => $this->ID(),
        ];
        $this->prerender = $this->Field();
    }

    public function Field($properties = [])
    {
        if (empty($properties) && $this->prerender) {
            /*
            Should be
            <select name="Invoices[GridFieldEditableColumns][XXX][RecordID]" class="dropdown editable-column-field" id="Form_FormField_GridFieldEditableColumns_XXX_RecordID">
            <option value="">Please select</option>
            <option value="ID">Name</option>
            ....
    */

            /*
            Prerender gives
 <select name="_Default" class="dropdown" id="Default">
    <option value="" selected="selected">Please select</option>
    <option value="ID">Name</option>
             */

            $this->prerender = str_replace('name="' . $this->prerenderAttrs['name'] . '"', 'name="' . $this->getName() . '"', $this->prerender);
            $this->prerender = str_replace('class="' . $this->prerenderAttrs['class'] . '"', 'class="' . $this->extraClass() . '"', $this->prerender);
            $this->prerender = str_replace('id="' . $this->prerenderAttrs['id'] . '"', 'id="' . $this->ID() . '"', $this->prerender);

            $this->prerender = str_replace('selected="selected"', "", $this->prerender);
            $this->prerender = str_replace('option value="' . $this->Value() . '"', 'option value="' . $this->Value() . '" selected="selected"', $this->prerender);
            return $this->prerender;
        }

        $result = parent::Field($properties);
        return $result;
    }

    public function Type()
    {
        return "dropdown";
    }
}
