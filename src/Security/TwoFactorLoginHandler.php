<?php

namespace LeKoala\Base\Security;

use LeKoala\Base\Forms\AlertField;
use LeKoala\Base\TextMessage\ProviderInterface;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
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
        /** @var ValidationResult $result */
        if ($member = $this->checkLogin($data, $request, $result)) {
            $session = $request->getSession();
            $session->set('TwoFactorLoginHandler.MemberID', $member->ID);
            $session->set('TwoFactorLoginHandler.Data', $data);

            if ($member->NeedTwoFactorAuth()) {
                return $this->redirect($this->link('step2'));
            }

            // 2FA is enabled but not needed, log in as normal
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
            $this
                ->getRequest()
                ->getSession()
                ->set('SessionForms.MemberLoginForm.Email', $data['Email'])
                ->set('SessionForms.MemberLoginForm.Remember', $rememberMe);
        }

        // Fail to login redirects back to form
        return $form->getRequestHandler()->redirectBackToForm();
    }

    /**
     * @return Member
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
        switch ($member->PreferredTwoFactorAuth()) {
            case 'text_message':
                $form = $this->textStepForm();

                // Send text message with token if needed
                $token = $this->getRequest()->getSession()->get('TwoFactorLoginHandler.TextToken');
                if (!$token) {
                    $token = (string) mt_rand(100000, 999999);
                    $this->getRequest()->getSession()->set('TwoFactorLoginHandler.TextToken', $token);
                    $message = _t('TwoFactorLoginHandler.YOUR_TOKEN_IS', "Your token is {token}", ['token' => $token]);
                    $provider = $this->getTextMessageProvider();
                    $provider->send($member, $message);
                }
                break;
            case 'totp':
                $form = $this->totpStepForm();
                break;
            default:
                return $this->redirectBack();
                break;
        }
        return [
            "Form" => $form,
        ];
    }

    /**
     * @return ProviderInterface
     */
    protected function getTextMessageProvider()
    {
        return  Injector::inst()->get(ProviderInterface::class);
    }

    public function twofactorcomplete()
    {
        return $this->redirectAfterSuccessfulLogin();
    }

    public function textStepForm()
    {
        $member = $this->getTwoFactorMember();
        $end = substr($member->Mobile, -4);
        $MessageSent =  new AlertField('MessageSent', _t('TwoFactorLoginHandler.MESSAGE_SENT', 'Your token has been sent to your phone ending with {end}', ['end' => $end]));
        $SecondFactor =  new TextField('SecondFactor', _t('TwoFactorLoginHandler.ENTER_YOUR_ACCESS_TOKEN', 'Enter your access token'));
        $validator = new RequiredFields('SecondFactor');
        $action = new FormAction('completeTextStep', _t('TwoFactorLoginHandler.VALIDATE_TOKEN', 'Validate token'));
        return new Form(
            $this,
            "textStepForm",
            new FieldList([
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
        return $this->redirectBack();
    }

    public function totpStepForm()
    {
        $SecondFactor =  new TextField('SecondFactor', _t('TwoFactorLoginHandler.ENTER_YOUR_ACCESS_TOKEN', 'Enter your access token'));
        $validator = new RequiredFields('SecondFactor');
        $action = new FormAction('completeTotpStep', _t('TwoFactorLoginHandler.VALIDATE_TOKEN', 'Validate token'));
        return new Form(
            $this,
            "totpStepForm",
            new FieldList([$SecondFactor]),
            new FieldList([$action]),
            $validator
        );
    }

    public function completeTotpStep($data, Form $form, HTTPRequest $request)
    {
        $session = $request->getSession();
        $member = $this->getTwoFactorMember();
        if ($member->validateTOTP($data['SecondFactor'])) {
            $data = $session->get('TwoFactorLoginHandler.Data');
            if (!$member) {
                return $this->redirectBack();
            }
            $this->performLogin($member, $data, $request);

            return $this->redirectAfterSuccessfulLogin();
        }

        // Fail to login redirects back to form
        return $this->redirectBack();
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
