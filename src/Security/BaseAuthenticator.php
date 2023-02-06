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
        if (TwoFactorMemberExtension::isEnabled()) {
            return TwoFactorLoginHandler::create($link, $this);
        }
        return LoginHandler::create($link, $this);
    }

    /**
     * @param array $data Form submitted data
     * @param ValidationResult $result
     * @param Member $member This third parameter is used in the CMSAuthenticator(s)
     * @return Member Found member, regardless of successful login
     */
    protected function authenticateMember($data, ValidationResult &$result = null, Member $member = null)
    {
        // For default admin, this will never call checkPassword, but it will call validateCanLogin
        return parent::authenticateMember($data, $result, $member);
    }

    /**
     * This is never called for default admin!
     *
     * @param Member|BaseMemberExtension $member
     * @param string $password
     * @param ValidationResult|null $result This can be null !
     * @return bool
     */
    public function checkPassword(Member $member, $password, ValidationResult &$result = null)
    {
        // Plain password: this should really not happen
        if ($member->Password && !$member->PasswordEncryption) {
            if ($member->Password == $password) {
                return true;
            }
        }

        if (!$member->Password || !$member->PasswordEncryption) {
            if ($result) {
                $result->addError(_t(
                    'BaseAuthenticator.PLEASERESET',
                    "Please reset your password with 'I've Lost my Password' steps below."
                ));
            }
            return false;
        }

        // Check empty password
        $encryptor = PasswordEncryptor::create_for_algorithm($member->PasswordEncryption);
        if ($encryptor->check($member->Password, "", $member->Salt, $member)) {
            if ($result) {
                $result->addError(_t(
                    'BaseAuthenticator.PLEASERESET',
                    "Please reset your password with 'I've Lost my Password' steps below."
                ));
                return false;
            }
        }

        // Check if the member entered his old password if he recently changed it
        // This prevent disclosing information and helps users
        $validator = Member::password_validator();
        if ($validator && $validator->getHistoricCount() > 0 && $member->isRecentPassword($password, 1)) {
            if ($result) {
                $result->addError(_t(
                    'BaseAuthenticator.OLDPASSWORD',
                    'You entered your old password. Please use the new one.'
                ));
            }
        }

        return parent::checkPassword($member, $password, $result);
    }

    /**
     * Needs to be enabled
     * @deprecated
     * @return boolean
     */
    public static function is2FAenabled()
    {
        return TwoFactorMemberExtension::isEnabled();
    }

    /**
     * Only check admins, also need is2FAenabled
     * @deprecated
     * @return boolean
     */
    public static function is2FAenabledAdminOnly()
    {
        return false;
    }

    /**
     * If ip is whitelisted, disable 2fa (true by default)
     * @deprecated
     * @return boolean
     */
    public static function is2FADisabledWhitelistedIps()
    {
        return Security::config()->disable_2fa_whitelisted_ips ? true : false;
    }
}
