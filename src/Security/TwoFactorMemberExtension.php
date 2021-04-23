<?php

namespace LeKoala\Base\Security;

use PragmaRX\Google2FA\Google2FA;
use SilverStripe\Forms\FieldList;
use LeKoala\Base\Forms\AlertField;
use LeKoala\Base\Helpers\IPHelper;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Security\BaseAuthenticator;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Security\DefaultAdminService;

/**
 * This extension is needed if you configure 2fa with:
 *
 * LeKoala\Base\Security\BaseAuthenticator:
 *   enable_2fa: true
 */
class TwoFactorMemberExtension extends DataExtension
{
    const METHOD_TOTP = 'totp';
    const METHOD_TEXT = 'text_message';

    private static $db = [
        'EnableTwoFactorAuth' => 'Boolean',
        'TOTPToken' => 'Varchar(32)',
    ];

    /**
     * @return boolean
     */
    public function NeedTwoFactorAuth()
    {
        //2fa is disabled globally
        if (!BaseAuthenticator::is2FAenabled()) {
            return false;
        }
        // the ip is whitelisted, no need for 2fa
        $adminIps = Security::config()->admin_ip_whitelist;
        if (!empty($adminIps)) {
            $request = Controller::curr()->getRequest();
            $requestIp = $request->getIP();
            if (IPHelper::checkIp($requestIp, $adminIps)) {
                return false;
            }
        }
        // It's the default admin and we have nothing configured yet
        $defaultAdmin = DefaultAdminService::getDefaultAdminUsername();
        if ($defaultAdmin && $defaultAdmin == $this->owner->Email && empty($this->AvailableTwoFactorMethod())) {
            return false;
        }
        // we only require 2fa for admins
        if (BaseAuthenticator::is2FAenabledAdminOnly() && $this->owner->EnableTwoFactorAuth) {
            return Permission::check('CMS_ACCESS', 'any', $this->owner);
        }
        return $this->owner->EnableTwoFactorAuth;
    }

    /**
     * @return array
     */
    public function AvailableTwoFactorMethod()
    {
        $arr = [];
        if ($this->owner->TOTPToken) {
            $arr[] = self::METHOD_TOTP;
        }
        if ($this->owner->Mobile) {
            $arr[] = self::METHOD_TEXT;
        }
        return $arr;
    }

    /**
     * @return string text_message, totp
     */
    public function PreferredTwoFactorAuth()
    {
        $arr = $this->AvailableTwoFactorMethod();
        if (!empty($arr)) {
            return $arr[0];
        }
        return false;
    }

    /**
     * Get the secret in base 32 format
     * @return string
     */
    public function generateTOTPSecretKey()
    {
        $google2fa = new Google2FA();
        return $google2fa->generateSecretKey();
    }

    /**
     * @param string $input
     * @return book
     */
    public function validateTOTP($input)
    {
        if (!$this->owner->TOTPToken) {
            return false;
        }
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($this->owner->TOTPToken, $input);
        return (bool)$valid;
    }

    /**
     * @link https://github.com/antonioribeiro/google2fa#generating-qrcodes
     * @return string
     */
    public function GoogleAuthenticatorQRCode()
    {
        $google2fa = new Google2FA();

        $label = SiteConfig::current_site_config()->Title;

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            $label,
            $this->owner->Email,
            $this->owner->TOTPToken
        );

        // TODO: support local generators
        $googleChartsURL = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($qrCodeUrl);

        return $googleChartsURL;
    }

    /**
     * @param FieldList $fields
     * @throws InvalidWriterException
     */
    public function updateCMSFields(FieldList $fields)
    {
        if (!$this->owner->exists()) {
            $fields->removeByName('TOTPToken');
        }

        $fields->removeByName('TOTPToken');
        if (strlen($this->owner->TOTPToken)) {
            $qrcodeURI = $this->GoogleAuthenticatorQRCode();
            $fields->addFieldToTab('Root.Main', ToggleCompositeField::create(
                null,
                _t('TwoFactorMemberExtension.CMSTOGGLEQRCODELABEL', 'Second Factor Token Secret'),
                LiteralField::create(null, sprintf("<img src=\"%s\" style=\"margin-left:10px\" loading=\"lazy\" />", $qrcodeURI))
            ));
        }

        if ($this->owner->EnableTwoFactorAuth && empty($this->AvailableTwoFactorMethod())) {
            $fields->insertAfter("EnableTwoFactorAuth", new AlertField("EnableTwoFactorAuthWarning", "No available authentication method"));
        }
    }

    /**
     * This is called by Member::validateCanLogin which is typically called in MemberAuthenticator::authenticate::authenticateMember
     * which is used in LoginHandler::doLogin::checkLogin
     *
     * This means canLogIn is called before 2FA, for instance
     *
     * @param ValidationResult $result
     * @return void
     */
    public function canLogIn(ValidationResult $result)
    {
        // Ip whitelist for users with cms access (empty by default)
        // SilverStripe\Security\Security:
        //   admin_ip_whitelist:
        //     - 127.0.0.1/255
        $adminIps = Security::config()->admin_ip_whitelist;
        $need2Fa = $this->owner->NeedTwoFactorAuth();
        $hasTwoFaMethods = count($this->owner->AvailableTwoFactorMethod()) > 0;

        // If we whitelist by IP, check we are using a valid IP
        if (!empty($adminIps)) {
            $request = Controller::curr()->getRequest();
            $isTrusted = false;

            // Request contains a specific header that we can trust
            $adminTrustedHeaders = Security::config()->admin_trusted_headers;
            if (!empty($adminTrustedHeaders)) {
                foreach ($adminTrustedHeaders as $trustedHeader) {
                    if ($request->getHeader($trustedHeader)) {
                        $isTrusted = true;
                    }
                }
            }

            // Default admin is trusted by default if no 2fa
            $defaultAdmin = DefaultAdminService::getDefaultAdminUsername();
            if ($defaultAdmin && $defaultAdmin == $this->owner->Email && !$hasTwoFaMethods) {
                $isTrusted = true;
            }

            $requestIp = $request->getIP();
            $isCmsUser = Permission::check('CMS_Access', 'any', $this->owner);
            if ($isCmsUser && !IPHelper::checkIp($requestIp, $adminIps)) {
                // No 2fa method to validate important account on invalid ips
                if (!$hasTwoFaMethods && !$isTrusted) {
                    $this->owner->audit('invalid_ip_admin', ['ip' => $requestIp]);
                    $result->addError(_t('TwoFactorMemberExtension.ADMIN_IP_INVALID', "Your ip address {address} is not whitelisted for this account level", ['address' => $requestIp]));
                }
            } else {
                // User has been whitelisted, no need for 2fa
                if (Config::inst()->get(BaseAuthenticator::class, 'disable_2fa_whitelisted_ips')) {
                    $need2Fa = false;
                }
            }
        }

        // Member need two factor auth but has no available method
        if ($need2Fa && !$hasTwoFaMethods) {
            $result->addError(_t('TwoFactorMemberExtension.YOU_NEED_2FA_METHOD', 'Your account needs two factor auth but does not have any available authentication method'));
        }
    }


    public function doGenerateTOTPToken()
    {
        $this->owner->TOTPToken = $this->generateTOTPSecretKey();
        $this->owner->write();

        return 'Token generated';
    }

    public function doClearTOTPToken()
    {
        $this->owner->TOTPToken = null;
        $this->owner->write();

        return 'Token cleared';
    }

    public function updateCMSActions(FieldList $actions)
    {
        if ($this->owner->EnableTwoFactorAuth && strlen($this->owner->TOTPToken) == 0) {
            $actions->push(new CustomAction("doGenerateTOTPToken", "Generate Token"));
        }
        if (Director::isDev() && $this->owner->TOTPToken) {
            $actions->push(new CustomAction("doClearTOTPToken", "Clear Token"));
        }
    }
}
