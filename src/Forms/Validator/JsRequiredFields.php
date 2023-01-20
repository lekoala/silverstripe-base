<?php

namespace LeKoala\Base\Forms\Validator;

use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\RequiredFields;
use LeKoala\Base\View\CommonRequirements;

/**
 */
class JsRequiredFields extends RequiredFields
{
    private static $vanilla_js = true;

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
    }

    public static function requirements(Form $form)
    {
        CommonRequirements::modularBehaviour();
        if (self::config()->vanilla_js) {
            Requirements::javascript("base/javascript/required-fields.js");
        } else {
            Requirements::javascript("base/javascript/RequiredFields.js");
        }
        $opts = [
            'skipParentClass' => "middleColumn"
        ];
        $form->setAttribute("data-mb-options", json_encode($opts, JSON_FORCE_OBJECT));
        $form->setAttribute("data-mb", "RequiredFields");
    }

    /**
     * @param Form $form
     * @return $this
     */
    public function setForm($form)
    {
        $this->form = $form;
        self::requirements($form);

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
            if ($formField && $formField->getAttribute("data-show-if")) {
                //TODO: parse expression properly
                continue;
            }

            // submitted data for file upload fields come back as an array
            $value = isset($data[$fieldName]) ? $data[$fieldName] : null;

            $error = '';
            if (is_array($value)) {
                if ($formField instanceof FileField && isset($value['error']) && $value['error']) {
                    $error = true;
                } else {
                    $error = (count($value)) ? false : true;
                }
            } elseif ($value !== null) {
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
