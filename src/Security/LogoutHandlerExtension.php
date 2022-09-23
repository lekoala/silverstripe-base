<?php

namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;

/**
 * Class \LeKoala\Base\Security\LogoutHandlerExtension
 *
 * @property \SilverStripe\Security\MemberAuthenticator\LogoutHandler|\LeKoala\Base\Security\LogoutHandlerExtension $owner
 */
class LogoutHandlerExtension extends Extension
{
    public function beforeLogout()
    {
        $user = Security::getCurrentUser();
        if ($user) {
            if ($user->hasMethod('beforeLogout')) {
                $user->beforeLogout();
            }
        }
    }

    public function afterLogout()
    {
    }

    public function failedLogout()
    {
    }
}
