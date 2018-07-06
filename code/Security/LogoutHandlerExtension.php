<?php
namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;

/**
 * Class \LeKoala\Base\Security\LogoutHandlerExtension
 *
 * @property \SilverStripe\Security\MemberAuthenticator\LogoutHandler|\LeKoala\Base\Security\LogoutHandlerExtension $owner
 */
class LogoutHandlerExtension extends Extension
{
    public function beforeLogin()
    {
    }

    public function afterLogin($member)
    {
    }

    public function failedLogin()
    {
    }
}
