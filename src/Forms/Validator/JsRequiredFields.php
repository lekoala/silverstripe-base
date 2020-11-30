<?php

namespace LeKoala\Base\Forms\Validator;

use SilverStripe\ORM\ArrayLib;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\RequiredFields;

/**
 */
class JsRequiredFields extends RequiredFields
{
    public function __construct()
    {
        parent::__construct();

        $required = func_get_args();
        if (isset($required[0]) && is_array($required[0])) {
            $required = $required[0];
        }
        if (!empty($required)) {
            $this->required = ArrayLib::valuekey($required);
        } else {
            $this->required = [];
        }

        Requirements::javascript("base/javascript/ModularBehaviour.js");
        Requirements::javascript("base/javascript/RequiredFields.js");
    }

    /**
     * @param Form $form
     * @return $this
     */
    public function setForm($form)
    {
        $this->form = $form;
        $this->form->setAttribute("data-module", "RequiredFields");
        return $this;
    }

    /**
     * Allows validation of fields via specification of a php function for
     * validation which is executed after the form is submitted.
     *
     * @param array $data
     *
     * @return boolean
     */
    public function php($data)
    {
        $valid = true;
        $fields = $this->form->Fields();

        // Run owns field validation
        foreach ($fields as $field) {
            $valid = ($field->validate($this) && $valid);
        }

        // Not required? Stays there
        if (!$this->required) {
            return $valid;
        }

        foreach ($this->required as $fieldName) {
            if (!$fieldName) {
                continue;
            }

            if ($fieldName instanceof FormField) {
                $formField = $fieldName;
                $fieldName = $fieldName->getName();
            } else {
                $formField = $fields->dataFieldByName($fieldName);
            }

            // Conditional ?
            if ($formField->getAttribute("data-show-if")) {
                //TODO: parse expression properly
                continue;
            }

            // submitted data for file upload fields come back as an array
            $value = isset($data[$fieldName]) ? $data[$fieldName] : null;

            if (is_array($value)) {
                if ($formField instanceof FileField && isset($value['error']) && $value['error']) {
                    $error = true;
                } else {
                    $error = (count($value)) ? false : true;
                }
            } else {
                // assume a string or integer
                $error = (strlen($value)) ? false : true;
            }

            if ($formField && $error) {
                $errorMessage = _t(
                    'SilverStripe\\Forms\\Form.FIELDISREQUIRED',
                    '{name} is required',
                    [
                        'name' => strip_tags(
                            '"' . ($formField->Title() ? $formField->Title() : $fieldName) . '"'
                        )
                    ]
                );

                if ($msg = $formField->getCustomValidationMessage()) {
                    $errorMessage = $msg;
                }

                $this->validationError(
                    $fieldName,
                    $errorMessage,
                    "required"
                );

                $valid = false;
            }
        }

        return $valid;
    }
}
