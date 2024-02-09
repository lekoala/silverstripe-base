<?php

namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;

/**
 * Class \LeKoala\Base\Security\LoginHandlerExtension
 *
 * @property \SilverStripe\Security\MemberAuthenticator\LoginHandler|\LeKoala\Base\Security\LoginHandlerExtension $owner
 */
class LoginHandlerExtension extends Extension
{
    public function beforeLogin()
    {
    }

    public function afterLogin($member)
    {
        if ($member->hasMethod('afterLogin')) {
            $member->afterLogin();
        }
    }

    public function failedLogin()
    {
    }
}
