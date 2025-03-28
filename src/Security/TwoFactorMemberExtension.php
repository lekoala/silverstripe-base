<?php

namespace LeKoala\Base\Security;

use PragmaRX\Google2FA\Google2FA;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use LeKoala\Base\Forms\AlertField;
use LeKoala\Base\Helpers\IPHelper;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Security\BaseAuthenticator;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Security\DefaultAdminService;

/**
 * This extension is needed if you configure 2fa with:
 *
 * LeKoala\Base\Security\BaseAuthenticator:
 * enable_2fa: true
 *
 * @property \SilverStripe\Security\Member|\LeKoala\Base\Security\TwoFactorMemberExtension $owner
 * @property ?string $PreferredTwoFactorMethod
 * @property bool|int $EnableTwoFactorAuth
 * @property ?string $TOTPToken
 */
class TwoFactorMemberExtension extends Extension
{
    const METHOD_TOTP = 'totp';
    const METHOD_TEXT = 'text_message';

    private static $db = [
        'PreferredTwoFactorMethod' => "Enum(',totp,text_message')",
        'EnableTwoFactorAuth' => 'Boolean',
        'TOTPToken' => 'Varchar(32)',
    ];

    public static function isEnabled()
    {
        /** @var Member $class */
        $class = singleton(Member::class);
        return $class->hasExtension(get_called_class());
    }

    public static function debugTwoFactorLoginInfos($member)
    {
        $adminIps = Security::config()->admin_ip_whitelist;
        $need2Fa = $member->NeedTwoFactorAuth();
        $requestIp = Controller::curr()->getRequest()->getIP();
        $isCmsUser = Permission::check('CMS_Access', 'any', $member);
        $ipCheck = IPHelper::checkIp($requestIp, $adminIps);
        $disableWhitelisted = Security::config()->disable_2fa_whitelisted_ips;
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
     * Do we need to show 2fa on login?
     * Disabled if:
     * - has trusted header
     * - is default admin and coming from dev ip range
     * - if 2fa is disabled for admin users and admin ip range is set
     * - if user disabled it (except if we need to trust ips)
     * @return boolean
     */
    public function NeedTwoFactorAuth()
    {
        $request = Controller::curr()->getRequest();
        $requestIp = $request->getIP();

        // Request contains a specific header that we can trust
        $adminTrustedHeaders = Security::config()->admin_trusted_headers;
        if (!empty($adminTrustedHeaders)) {
            foreach ($adminTrustedHeaders as $trustedHeader) {
                if ($request->getHeader($trustedHeader)) {
                    return false;
                }
            }
        }

        // It's the default admin and we have nothing configured yet
        $defaultAdmin = DefaultAdminService::getDefaultAdminUsername();
        $isDefaultAdmin = $defaultAdmin && $defaultAdmin == $this->owner->Email;
        $devIps = Security::config()->dev_ip_whitelist;
        if ($isDefaultAdmin && !empty($devIps)) {
            // In dev mode or coming from trusted dev ip?
            $isDevIp = is_array($devIps) ? IPHelper::checkIp($requestIp, $devIps) : false;
            if (Director::isDev() || $isDevIp) {
                return false;
            } else {
                return true;
            }
        }

        // do we check admin ips ?
        $adminIps = Security::config()->admin_ip_whitelist;
        if (!empty($adminIps) && !$isDefaultAdmin && Permission::check('CMS_ACCESS', 'any', $this->owner)) {
            $disabledForAdmin = Security::config()->disable_2fa_whitelisted_ips;
            if (IPHelper::checkIp($requestIp, $adminIps)) {
                // we disabled 2fa for admin if coming from trusted ip range
                if ($disabledForAdmin) {
                    return false;
                }
            } else {
                // It needs 2fa to be trusted, and therefore cannot login if 2fa is not enabled for that user
                return true;
            }
        }

        // Is it enabled ?
        return $this->owner->EnableTwoFactorAuth ? true : false;
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
        if ($this->owner->Mobile && TwoFactorLoginHandler::getTextMessageProvider()) {
            $arr[] = self::METHOD_TEXT;
        }
        return $arr;
    }

    public static function listTwoFactorMethods($list = null)
    {
        $arr[self::METHOD_TOTP] = _t('TwoFactorMemberExtension.METHOD_TOTP', 'TOTP');
        $arr[self::METHOD_TEXT] = _t('TwoFactorMemberExtension.METHOD_TEXT', 'Text message');
        if (is_array($list)) {
            $new = [];
            foreach ($list as $k) {
                $new[$k] = $arr[$k];
            }
            return $new;
        }
        return $arr;
    }

    public static function EnabledTwoFactorMethods()
    {
        $arr[] = self::METHOD_TOTP;
        if (TwoFactorLoginHandler::getTextMessageProvider()) {
            $arr[] = self::METHOD_TEXT;
        }
        return $arr;
    }

    /**
     * Returns the first available method according to user preference
     * or in this order:
     * - TOTP
     * - Mobile
     *
     * @return string text_message, totp
     */
    public function PreferredTwoFactorAuth()
    {
        if ($this->owner->PreferredTwoFactorMethod) {
            return $this->owner->PreferredTwoFactorMethod;
        }
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
        $window = Security::config()->window_2fa ?? 4; // number of keys to check, helps if server is out of sync
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($this->owner->TOTPToken, $input, $window);

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
        // $googleChartsURL = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($qrCodeUrl);
        $remoteUrl = "https://qrcode.tec-it.com/API/QRCode?data=" . urlencode($qrCodeUrl);

        return $remoteUrl;
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
        if ($this->owner->TOTPToken && strlen($this->owner->TOTPToken)) {
            $qrcodeURI = $this->GoogleAuthenticatorQRCode();
            $fields->addFieldToTab('Root.Main', ToggleCompositeField::create(
                null,
                _t('TwoFactorMemberExtension.CMSTOGGLEQRCODELABEL', 'Second Factor Token Secret'),
                LiteralField::create(null, sprintf("<img src=\"%s\" loading=\"lazy\" style=\"width:200px;height:auto;margin-left:10px\" />", $qrcodeURI))
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
     * To prevent login, add errors to validation result
     *
     * @param ValidationResult $result
     * @return void
     */
    public function canLogIn(ValidationResult $result)
    {
        /** @var Member|TwoFactorMemberExtension $owner */
        $owner = $this->owner;
        $need2Fa = $owner->NeedTwoFactorAuth();
        $hasTwoFaMethods = count($owner->AvailableTwoFactorMethod()) > 0;

        // Member need two factor auth but has no available method
        if ($need2Fa && !$hasTwoFaMethods) {
            $result->addError(_t('TwoFactorMemberExtension.YOU_NEED_2FA_METHOD', 'Your account needs two factor auth but does not have any available authentication method'));
        }
    }

    public function Is2FaConfigured()
    {
        return $this->owner->EnableTwoFactorAuth && count($this->owner->AvailableTwoFactorMethod()) > 0;
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
        if ($this->owner->EnableTwoFactorAuth && strlen($this->owner->TOTPToken ?? '') == 0) {
            $actions->push(new CustomAction("doGenerateTOTPToken", "Generate Token"));
        }
        if (Director::isDev() && $this->owner->TOTPToken) {
            $actions->push(new CustomAction("doClearTOTPToken", "Clear Token"));
        }
    }
}
