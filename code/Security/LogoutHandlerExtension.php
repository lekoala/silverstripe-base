<?php
namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;

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
