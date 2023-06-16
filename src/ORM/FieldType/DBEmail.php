<?php

namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\Forms\NullableField;
use LeKoala\FormElements\InputMaskField;

/**
 */
class DBEmail extends DBUntranslatedVarchar
{
    public function __construct($name = null, $options = [])
    {
        //@link https://www.rfc-editor.org/errata_search.php?rfc=3696
        parent::__construct($name, 254, $options);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        // Set field with appropriate size
        $field = InputMaskField::create($this->name, $title);
        $field->setAlias(InputMaskField::ALIAS_EMAIL);
        $field->setMaxLength($this->getSize());

        // Allow the user to select if it's null instead of automatically assuming empty string is
        if (!$this->getNullifyEmpty()) {
            return NullableField::create($field);
        }
        return $field;
    }
}
