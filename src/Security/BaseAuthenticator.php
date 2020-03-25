<?php

namespace LeKoala\Base\Security;

use LeKoala\Base\Helpers\IPHelper;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Security\Permission;
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

    public static function debugTwoFactorLoginInfos($member)
    {
        $adminIps = Security::config()->admin_ip_whitelist;
        $need2Fa = $member->NeedTwoFactorAuth();
        $requestIp = Controller::curr()->getRequest()->getIP();
        $isCmsUser = Permission::check('CMS_Access', 'any', $member);
        $ipCheck = IPHelper::checkIp($requestIp, $adminIps);
        $disableWhitelisted = Config::inst()->get(BaseAuthenticator::class, 'disable_2fa_whitelisted_ips');
        $available2fa = $member->AvailableTwoFactorMethod();
        return [
            'admin_ips' => $adminIps,
            'need_2fa' => $need2Fa,
            'request_ip' => $requestIp,
            'is_cms_user' => $isCmsUser,
            'ip_check' => $ipCheck,
            'disable_whitelisted' => $disableWhitelisted,
            'available_2fa' => $available2fa,
        ];
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
