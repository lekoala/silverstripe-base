<?php
namespace LeKoala\Base\Forms\Validator;

use SilverStripe\Forms\RequiredFields;
use SilverStripe\View\Requirements;

/**
 * You also need to use
 *
 *     $this->setAttribute("data-module", "RequiredFields");
 *
 * on your form
 */
class JsRequiredFields extends RequiredFields
{
    public function __construct()
    {
        parent::__construct();

        Requirements::javascript("base/javascript/ModularBehaviour.js");
        Requirements::javascript("base/javascript/RequiredFields.js");
    }
}
