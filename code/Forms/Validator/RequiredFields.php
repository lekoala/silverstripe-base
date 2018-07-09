<?php
namespace LeKoala\Base\Forms\Validator;

use SilverStripe\Forms\RequiredFields as DefaultRequiredFields;
use SilverStripe\View\Requirements;

class RequiredFields extends DefaultRequiredFields
{
    public function __construct()
    {
        parent::__construct();

        Requirements::javascript("base/javascript/RequiredFields");
    }
}
