<?php

namespace LeKoala\Base\Security;

use PragmaRX\Google2FA\Google2FA;
use SilverStripe\Forms\FieldList;
use LeKoala\Base\Helpers\IPHelper;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Security\BaseAuthenticator;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\Forms\ToggleCompositeField;

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
        // the ip is whitelisted
        $adminIps = Security::config()->admin_ip_whitelist;
        if (!empty($adminIps)) {
            $request = Controller::curr()->getRequest();
            $requestIp = $request->getIP();
            if (IPHelper::checkIp($requestIp, $adminIps)) {
                // return false;
            }
        }
        // we only required 2fa for admins
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

        if (strlen($this->owner->TOTPToken)) {
            $qrcodeURI = $this->GoogleAuthenticatorQRCode();
            $fields->addFieldToTab('Root.Main', ToggleCompositeField::create(
                null,
                _t('TwoFactorMemberExtension.CMSTOGGLEQRCODELABEL', 'Second Factor Token Secret'),
                LiteralField::create(null, sprintf("<img src=\"%s\" style=\"margin-left:10px\" loading=\"lazy\" />", $qrcodeURI))
            ));
            $fields->removeByName('TOTPToken');
        }
    }

    public function doGenerateTOTPToken()
    {
        $this->owner->TOTPToken = $this->generateTOTPSecretKey();
        $this->owner->write();

        return 'Token generated';
    }

    public function updateCMSActions(FieldList $actions)
    {
        if ($this->owner->EnableTwoFactorAuth && strlen($this->owner->TOTPToken) == 0) {
            $actions->push(new CustomAction("doGenerateTOTPToken", "Generate Token"));
        }
    }
}
