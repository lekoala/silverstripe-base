<?php

namespace LeKoala\Base\Security;

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
        'secondStepForm',
        'twofactorsetup',
        'twoFactorSetupFrom',
        'verify_and_activate',
        'twofactorcomplete',
        'show_backup_tokens',
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

    protected function getTwoFactorMember()
    {
        $id = $this->getRequest()->getSession()->get('TwoFactorLoginHandler.MemberID');
        if ($id) {
            return DataObject::get_by_id(Member::class, $id);
        }
    }

    public function step2()
    {
        return [
            "Form" => $this->secondStepForm(),
        ];
    }

    public function twofactorsetup()
    {
        return [
            "Form" => $this->twoFactorSetupFrom()
        ];
    }

    public function twofactorcomplete()
    {
        return $this->redirectAfterSuccessfulLogin();
    }

    public function twoFactorSetupFrom()
    {
        $session  = $this->request->getSession();
        $memberID = $session->get('TwoFactorLoginHandler.MemberID');
        $member   = Member::get()->byID($memberID);
        $member->generateTOTPToken();
        $member->write();

        return $member
            ->customise(array(
                'CurrentController' => $this,
            ))
            ->renderWith('TokenInfoDialog');
    }

    /**
     * Function to allow verification & activation of two-factor-auth via Ajax
     *
     * @param $request
     * @return \SS_HTTPResponse
     */
    public function verify_and_activate($request)
    {
        $session  = $this->request->getSession();
        $memberID = $session->get('TwoFactorLoginHandler.MemberID');
        $member   = Member::get()->byID($memberID);
        if (!$member) {
            return;
        }

        $TokenCorrect = $member->validateTOTP(
            (string) $request->postVar('VerificationInput')
        );

        if ($TokenCorrect) {
            $member->Has2FA = true;
            $member->regenerateBackupTokens();
            $member->write();

            $data = $session->get('TwoFactorLoginHandler.Data');
            if (!$member) {
                return $this->redirectBack();
            }
            $this->performLogin($member, $data, $request);

            return $this->redirect($this->link('show_backup_tokens'));
        }

        // else: show feedback
        return [
            "Form" => $member
                ->customise(
                    [
                        'CurrentController' => $this,
                        'VerificationError' => true,
                    ]
                )
                ->renderWith('TokenInfoDialog')
        ];
    }

    public function show_backup_tokens()
    {
        $member = Security::getCurrentUser();

        if (!$member->BackupTokens()->count()) {
            $member->regenerateBackupTokens();
        }

        return [
            "Title" => 'Two Factor Back Up Tokens',
            "Content" => $member->customise(array(
                "backUrl" => $this->getBackURL()
            ))
                ->renderWith('ShowBackUpTokens')
        ];
    }

    public function secondStepForm()
    {
        $SecondFactor =  new TextField('SecondFactor', _t('TwoFactorLoginHandler.ENTER_YOUR_ACCESS_TOKEN', 'Enter your access token'));
        $validator = new RequiredFields('SecondFactor');
        $action = new FormAction('completeSecondStep', _t('TwoFactorLoginHandler.VALIDATE_TOKEN', 'Validate token'));
        return new Form(
            $this,
            "secondStepForm",
            new FieldList([$SecondFactor]),
            new FieldList([$action]),
            $validator
        );
    }

    public function completeSecondStep($data, Form $form, HTTPRequest $request)
    {
        $session = $request->getSession();
        $memberID = $session->get('TwoFactorLoginHandler.MemberID');
        $member = Member::get()->byID($memberID);
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
