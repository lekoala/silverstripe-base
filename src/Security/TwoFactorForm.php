<?php

namespace LeKoala\Base\Security;

use LeKoala\Base\Forms\BaseForm;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use LeKoala\Base\Forms\AlertField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use LeKoala\Base\Security\TwoFactorMemberExtension;
use SilverStripe\Forms\DropdownField;

/**
 * This class can be used to control 2fa for a given member
 */
class TwoFactorForm extends BaseForm
{
    public function __construct(Controller $controller, $name = 'TwoFactorForm', FieldList $fields = null, FieldList $actions = null, $validator = null)
    {
        $member = Security::getCurrentUser();

        $fields = new FieldList();

        $needConfirmation = $this->getRequest()->getSession()->get("TwoFactorForm.NeedConfirmation");

        if ($needConfirmation) {
            $fields->push(new AlertField("NeedConfirmation", _t('TwoFactorForm.NEED_CONFIRMATION', "Please scan the QR code and enter the confirmation code to enable Two Factor Authentication")));
            $qrcodeURI = $member->GoogleAuthenticatorQRCode();
            $fields->push(LiteralField::create(null, sprintf("<img src=\"%s\" style=\"margin-left:10px\" width=\"200\" height=\"200\" />", $qrcodeURI)));
            $SecondFactor =  new TextField('SecondFactor', _t('TwoFactorLoginHandler.ENTER_YOUR_ACCESS_TOKEN', 'Enter your access token'));
            $SecondFactor->setAttribute("size", 6);
            $SecondFactor->setAttribute("placeholder", "000000");
            $fields->push($SecondFactor);
            $actions = new FieldList();
            $actions->push($doSave = new FormAction('doSave', _t('TwoFactorForm.DOCONFIRM', 'Confirm code and enable 2FA')));
            $actions->push($doCancel = new FormAction('doCancel', _t('TwoFactorForm.DOCANCEL', 'Cancel')));
            $doCancel->addExtraClass("btn-secondary btn-cancel");
        } else {
            if ($member->EnableTwoFactorAuth) {
                $fields->push(new CheckboxField('DisableTwoFactorAuth', _t('TwoFactorFORM.DODISABLE', 'Disable Two Factor Authentication')));
            } else {
                $fields->push(new CheckboxField('EnableTwoFactorAuth', _t('TwoFactorForm.DOENABLE', 'Enable Two Factor Authentication')));
                // $methods = TwoFactorMemberExtension::listTwoFactorMethods(TwoFactorMemberExtension::EnabledTwoFactorMethods());
                // $fields->push(new DropdownField('PreferredTwoFactorMethod', _t('TwoFactorForm.2FA_METHOD', 'Preferred method'), $methods));
                $fields->push(new AlertField('NeedConfirmation', _t('TwoFactorForm.NEED_VERIFY', 'You will need to verify your secondary authentication method on the next step')));
            }

            $actions = new FieldList();
            $actions->push($doSave = new FormAction('doSave', _t('TwoFactorForm.DOUPDATE', 'Update my settings')));
        }

        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    public function doCancel($data)
    {
        $this->getRequest()->getSession()->clear("TwoFactorForm.NeedConfirmation");
        return $this->getController()->redirectBack();
    }

    public function doSave($data)
    {
        /** @var Member|TwoFactorMemberExtension $member */
        $member = Security::getCurrentUser();

        $enable = $data['EnableTwoFactorAuth'] ?? false;
        $disable = $data['DisableTwoFactorAuth'] ?? false;
        $confirm = $data['SecondFactor'] ?? null;

        $needConfirmation = $this->getRequest()->getSession()->get("TwoFactorForm.NeedConfirmation");

        if ($disable) {
            $member->EnableTwoFactorAuth = false;
            $member->TOTPToken = null;
            $member->PreferredTwoFactorMethod = null;
            $member->write();
            return $this->success(_t('TwoFactorForm.DISABLECONFIRM', "You have disabled Two Factor Authentication"));
        }

        if ($enable) {
            // Show the qr code and access a confirmation code
            $member->doGenerateTOTPToken();

            $this->getRequest()->getSession()->set("TwoFactorForm.NeedConfirmation", true);

            return $this->getController()->redirectBack();
        }

        if ($needConfirmation) {
            if ($member->validateTOTP($confirm)) {
                $this->getRequest()->getSession()->clear("TwoFactorForm.NeedConfirmation");
                $member->EnableTwoFactorAuth = true;
                $member->PreferredTwoFactorMethod = TwoFactorMemberExtension::METHOD_TOTP;
                $member->write();
                return $this->success(_t('TwoFactorForm.2FAENABLED', "You have enabled Two Factor Authentication"));
            } else {
                return $this->error(_t('TwoFactorForm.INVALIDCODE', "The code is invalid, please try again"));
            }
        }

        return $this->getController()->redirectBack();
    }
}
