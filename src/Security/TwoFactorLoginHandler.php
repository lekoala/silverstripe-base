<?php

namespace LeKoala\Base\Security;

use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use LeKoala\Base\Forms\AlertField;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Core\Injector\Injector;
use LeKoala\Base\Security\BaseMemberExtension;
use LeKoala\Base\TextMessage\ProviderInterface;
use LeKoala\Base\Security\TwoFactorMemberExtension;
use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;

/**
 * Improved login handler returned by BaseAuthenticator if 2fa is enabled
 *
 * @link https://github.com/camfindlay/silverstripe-twofactorauth/blob/master/src/Handlers/LoginHandler.php
 */
class TwoFactorLoginHandler extends LoginHandler
{
    private static $allowed_actions = [
        'step2',
        'sendnew',
        'totpStep',
        'textStep',
        'twofactorcomplete',
        'completeTotpStep',
        'completeTextStep',
        'textStepForm',
        'totpStepForm',
    ];

    public function doLogin($data, MemberLoginForm $form, HTTPRequest $request)
    {
        $this->extend('beforeLogin');

        // Successful login
        /** @var Member|BaseMemberExtension|TwoFactorMemberExtension $member */
        if ($member = $this->checkLogin($data, $request, $result)) {
            if ($member->NeedTwoFactorAuth()) {
                $session = $request->getSession();
                $session->set('TwoFactorLoginHandler.MemberID', $member->ID);
                // Don't forget to clear this afterwards
                // Never store password
                unset($data['Password']);
                $session->set('TwoFactorLoginHandler.Data', $data);
                return $this->redirect(self::getStep2Link());
            }

            // 2FA is enabled but not needed, log in as normal
            // otherwise this will be done after step2
            $this->performLogin($member, $data, $request);

            // Allow operations on the member after successful login
            $this->extend('afterLogin', $member);

            return $this->redirectAfterSuccessfulLogin();
        }

        $this->extend('failedLogin');

        $message = implode("; ", array_map(
            function ($message) {
                return $message['message'];
            },
            $result->getMessages()
        ));

        $form->sessionMessage($message, 'bad');

        // Failed login

        /** @skipUpgrade */
        if (array_key_exists('Email', $data)) {
            $rememberMe = (isset($data['Remember']) && Security::config()->get('autologin_enabled') === true);
            $request
                ->getSession()
                ->set('SessionForms.MemberLoginForm.Email', $data['Email'])
                ->set('SessionForms.MemberLoginForm.Remember', $rememberMe);
        }

        // Fail to login redirects back to form
        return $form->getRequestHandler()->redirectBackToForm();
    }

    /**
     * @return Member|TwoFactorMemberExtension
     */
    protected function getTwoFactorMember()
    {
        $id = $this->getRequest()->getSession()->get('TwoFactorLoginHandler.MemberID');
        if ($id) {
            return DataObject::get_by_id(Member::class, $id);
        }
    }

    public function step2()
    {
        $member = $this->getTwoFactorMember();
        if (!$member) {
            return $this->redirectBack();
        }
        $form = null;
        switch ($member->PreferredTwoFactorAuth()) {
            case 'text_message':
                $form = $this->textStepForm();

                // Send text message with token if needed
                $token = $this->getRequest()->getSession()->get('TwoFactorLoginHandler.TextToken');
                if (!$token) {
                    $this->doSendTextMessage();
                }
                break;
            case 'totp':
                $form = $this->totpStepForm();
                break;
            default:
                return $this->redirectBack();
                break;
        }

        $session = $this->getRequest()->getSession();
        if ($session->get('TwoFactorLoginHandler.ErrorMessage')) {
            $message = $session->get('TwoFactorLoginHandler.ErrorMessage');
            $session->clear('TwoFactorLoginHandler.ErrorMessage');
            $form->Fields()->push(new AlertField("TwoFactorError", $message, "danger"));
        }

        // Go back link
        $form->Actions()->push(new LiteralField("BackDivider", "<hr/>"));
        $form->Actions()->push(new LiteralField("BackLink", '<a href="/Security/login">' . _t('TwoFactorLoginHandler.GOBACK', 'Back to login screen') . '</a>'));

        return [
            "Form" => $form,
        ];
    }

    protected function doSendTextMessage()
    {
        $member = $this->getTwoFactorMember();
        if ($member) {
            $token = (string) mt_rand(100000, 999999);
            $this->getRequest()->getSession()->set('TwoFactorLoginHandler.TextToken', $token);
            $message = _t('TwoFactorLoginHandler.YOUR_TOKEN_IS', "Your token is {token}", ['token' => $token]);
            $provider = self::getTextMessageProvider();
            $provider->send($member, $message);
            return true;
        }
        return false;
    }

    public function sendnew()
    {
        $this->doSendTextMessage();
        return $this->redirectBack();
    }

    /**
     * @return ProviderInterface
     */
    public static function getTextMessageProvider()
    {
        return Injector::inst()->get(ProviderInterface::class);
    }

    public function twofactorcomplete()
    {
        return $this->redirectAfterSuccessfulLogin();
    }

    public function textStepForm()
    {
        $member = $this->getTwoFactorMember();
        $end = substr($member->Mobile, -4);
        $Header = new HeaderField("TwoFactorHeader", _t('TwoFactorLoginHandler.TWOFA_HEADER', 'Two-factor Authentication'), 1);
        $MessageSent =  new AlertField('MessageSent', _t('TwoFactorLoginHandler.MESSAGE_SENT', 'Your token has been sent to your phone ending with {end}', ['end' => $end]));
        $SecondFactor =  new TextField('SecondFactor', _t('TwoFactorLoginHandler.ENTER_YOUR_ACCESS_TOKEN', 'Enter your access token'));
        $SecondFactor->setAttribute("size", 6);
        $SecondFactor->setAttribute("placeholder", "000000");
        $validator = new RequiredFields('SecondFactor');
        $action = new FormAction('completeTextStep', _t('TwoFactorLoginHandler.VALIDATE_TOKEN', 'Validate token'));
        $sendNew = new LiteralField("sendnew", '<p><a href="' . $this->Link('sendnew') . '">' . _t('TwoFactorLoginHandler.SEND_NEW_TOKEN', 'Send a new code') . '</a></p>');
        return new Form(
            $this,
            "textStepForm",
            new FieldList([
                $Header,
                $MessageSent,
                $SecondFactor,
            ]),
            new FieldList([$action]),
            $validator
        );
    }

    public function completeTextStep($data, Form $form, HTTPRequest $request)
    {
        $session = $request->getSession();
        $member = $this->getTwoFactorMember();

        $token = $this->getRequest()->getSession()->get('TwoFactorLoginHandler.TextToken');
        if ($data['SecondFactor'] == $token) {
            $data = $session->get('TwoFactorLoginHandler.Data');
            if (!$member) {
                return $this->redirectBack();
            }
            $this->performLogin($member, $data, $request);

            return $this->redirectAfterSuccessfulLogin();
        }

        // Fail to login redirects back to form
        $session->set('TwoFactorLoginHandler.ErrorMessage', _t('TwoFactorLoginHandler.ERRORMESSAGE', 'The provided token is invalid, please try again.'));

        return $this->redirect(self::getStep2Link());
    }

    public static function getStep2Link()
    {
        return '/Security/login/default/step2';
    }

    public function totpStepForm()
    {
        $Header = new HeaderField("TwoFactorHeader", _t('TwoFactorLoginHandler.TWOFA_HEADER', 'Two-factor Authentication'), 1);
        $HelpMessage =  new AlertField('HelpMessage', _t('TwoFactorLoginHandler.USE_YOUR_AUTH_APP', 'Open your authenticator app to generate your code'));
        $SecondFactor =  new TextField('SecondFactor', _t('TwoFactorLoginHandler.ENTER_YOUR_ACCESS_TOKEN', 'Enter your access token'));
        $SecondFactor->setAttribute("size", 6);
        $SecondFactor->setAttribute("placeholder", "000000");
        $validator = new RequiredFields('SecondFactor');
        $action = new FormAction('completeTotpStep', _t('TwoFactorLoginHandler.VALIDATE_TOKEN', 'Validate token'));
        return new Form(
            $this,
            "totpStepForm",
            new FieldList([
                $Header,
                $HelpMessage,
                $SecondFactor,
            ]),
            new FieldList([$action]),
            $validator
        );
    }

    public function completeTotpStep($data, Form $form, HTTPRequest $request)
    {
        $session = $request->getSession();
        $member = $this->getTwoFactorMember();
        if ($member && $member->validateTOTP($data['SecondFactor'])) {
            $data = $session->get('TwoFactorLoginHandler.Data');
            if (!$member) {
                return $this->redirectBack();
            }
            $this->performLogin($member, $data, $request);
            return $this->redirectAfterSuccessfulLogin();
        }

        // Fail to login redirects back to form
        $session->set('TwoFactorLoginHandler.ErrorMessage', _t('TwoFactorLoginHandler.ERRORMESSAGE', 'The provided token is invalid, please try again.'));
        return $this->redirect(self::getStep2Link());
    }

    public function performLogin($member, $data, HTTPRequest $request)
    {
        $member = parent::performLogin($member, $data, $request);

        $session  = $this->request->getSession();
        $data = $session->clear('TwoFactorLoginHandler');

        return $member;
    }

    public function getBackURL()
    {
        $session  = $this->request->getSession();
        $backURL = null;
        $data = $session->get('TwoFactorLoginHandler.Data');
        if ($data && isset($session->get('TwoFactorLoginHandler.Data')['BackURL'])) {
            $backURL = $session->get('TwoFactorLoginHandler.Data')['BackURL'];
        }
        if ($backURL && Director::is_site_url($backURL)) {
            return $backURL;
        }
        return parent::getBackURL();
    }
}
