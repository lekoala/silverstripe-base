<?php

namespace LeKoala\Base\Security;

use SilverStripe\Security\Member;
use LeKoala\Base\Helpers\IPHelper;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\MemberPassword;
use SilverStripe\Security\PasswordEncryptor;
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

    protected function authenticateMember($data, ValidationResult &$result = null, Member $member = null)
    {
        return parent::authenticateMember($data, $result, $member);
    }

    public function checkPassword(Member $member, $password, ValidationResult &$result = null)
    {
        // Plain password
        if ($member->Password && !$member->PasswordEncryption) {
            if ($member->Password == $password) {
                return $result;
            }
        }

        if(!$member->PasswordEncryption) {
            $result->addError(_t(
                'BaseAuthenticator.PLEASERESET',
                "Please reset your password with 'I've Lost my Password' steps below."
            ));
            return $result;
        }

        // Check empty password
        $encryptor = PasswordEncryptor::create_for_algorithm($member->PasswordEncryption);
        if ($encryptor->check($member->Password, "", $member->Salt, $member)) {
            $result->addError(_t(
                'BaseAuthenticator.PLEASERESET',
                "Please reset your password with 'I've Lost my Password' steps below."
            ));
        }

        // Check if the member entered his old password if he recently changed it
        // This prevent disclosing information and helps users
        $previousPasswords = MemberPassword::get()
            ->where(['"MemberPassword"."MemberID"' => $member->ID])
            ->sort('"Created" DESC, "ID" DESC')
            ->limit(2);

        $i = 0;
        $recentChange = false;
        foreach ($previousPasswords as $previousPassword) {
            // The first password is the current one, check if it's a new one
            $i++;
            if ($i == 1) {
                if (strtotime($previousPassword->Created) >= strtotime('-3 days')) {
                    $recentChange = true;
                }
            }
            if ($i == 2 && $recentChange) {
                if ($previousPassword->checkPassword($password)) {
                    $result->addError(_t(
                        'BaseAuthenticator.OLDPASSWORD',
                        'You entered your old password. Please use the new one.'
                    ));
                    return $result;
                }
            }
        }

        return parent::checkPassword($member, $password, $result);
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
