<?php

namespace LeKoala\Base\Security;

use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\Control\HTTPRequest;
use LeKoala\Base\Security\ConstantTimeOperation;

class ConstantLoginHandler extends LoginHandler
{
    public function doLogin($data, MemberLoginForm $form, HTTPRequest $request)
    {
        return ConstantTimeOperation::execute(fn() => parent::doLogin($data, $form, $request));
    }
}
