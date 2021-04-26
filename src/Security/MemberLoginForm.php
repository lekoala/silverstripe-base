<?php

namespace LeKoala\Base\Security;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\EmailField;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm as DefaultMemberLoginForm;

class MemberLoginForm extends DefaultMemberLoginForm
{
    // public function setAuthenticatorClass($class)
    // {
    //     if ($class == MemberAuthenticator::class) {
    //         $class = BaseAuthenticator::class;
    //     }
    //     return parent::setAuthenticatorClass($class);
    // }

    protected function getFormFields()
    {
        $fields = parent::getFormFields();

        // Fix strange crash in chrome with anchors in combination with autofocus
        $Email = $this->getEmailField($fields);
        if ($Email) {
            $Email->setAttribute("autofocus", null);
        }
        return $fields;
    }

    /**
     * @param FieldList $fields
     * @return EmailField
     */
    protected function getEmailField($fields)
    {
        return $fields->dataFieldByName('Email');
    }
}
