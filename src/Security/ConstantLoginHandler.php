<?php

namespace LeKoala\Base\Security;

use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\Control\HTTPRequest;
use LeKoala\Base\Security\ConstantTimeOperation;

/**
 * This is now improved in 5.4, but the fixed time only apply to the authenticator itself
 * Any before/after login hook are not included
 * This class helps making sure that no operation can have any side effect
 */
class ConstantLoginHandler extends LoginHandler
{
    public function doLogin($data, MemberLoginForm $form, HTTPRequest $request)
    {
        return ConstantTimeOperation::execute(fn() => parent::doLogin($data, $form, $request));
    }
}
