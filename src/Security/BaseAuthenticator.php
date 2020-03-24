<?php

namespace LeKoala\Base\Security;

use SilverStripe\Core\Config\Config;
use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

/**
 * Improve authentification to allow 2fa
 */
class BaseAuthenticator extends MemberAuthenticator
{
  /**
     * Selects the login handler based on enable_2fa
     *
     * @param string $link
     * @return LoginHandler
     */
    public function getLoginHandler($link)
    {
        if (self::is2FAenabled()) {
            return TwoFactorLoginHandler::create($link, $this);
        }

        return LoginHandler::create($link, $this);
    }

    /**
     * Needs to be enabled
     *
     * LeKoala\Base\Security\BaseAuthenticator:
     *   enable_2fa: true
     *
     * @return boolean
     */
    public static function is2FAenabled()
    {
        return Config::inst()->get(BaseAuthenticator::class, 'enable_2fa');
    }

    /**
     * Only check admins, also need is2FAenabled
     *
     * LeKoala\Base\Security\BaseAuthenticator:
     *   enable_2fa_admin_only: true
     *
     * @return boolean
     */
    public static function is2FAenabledAdminOnly()
    {
        return Config::inst()->get(BaseAuthenticator::class, 'enable_2fa_admin_only');
    }

    /**
     * If ip is whitelisted, disable 2fa (true by default)
     *
     * LeKoala\Base\Security\BaseAuthenticator:
     *   disable_2fa_whitelisted_ips: true
     *
     * @return boolean
     */
    public static function is2FADisabledWhitelistedIps()
    {
        return Config::inst()->get(BaseAuthenticator::class, 'disable_2fa_whitelisted_ips');
    }
}
