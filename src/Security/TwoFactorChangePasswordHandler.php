<?php

namespace LeKoala\Base\Security;

use SilverStripe\Security\MemberAuthenticator\ChangePasswordHandler;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use LeKoala\Base\Security\TwoFactorLoginHandler;

class TwoFactorChangePasswordHandler extends ChangePasswordHandler
{
    /**
     * Change the password
     *
     * @param array $data The user submitted data
     * @param ChangePasswordForm $form
     * @return HTTPResponse
     * @throws ValidationException
     * @throws NotFoundExceptionInterface
     */
    public function doChangePassword(array $data, $form)
    {
        $member = Security::getCurrentUser();
        // The user was logged in, check the current password
        $oldPassword = isset($data['OldPassword']) ? $data['OldPassword'] : null;
        if ($member && !$this->checkPassword($member, $oldPassword)) {
            $form->sessionMessage(
                _t(
                    'SilverStripe\\Security\\Member.ERRORPASSWORDNOTMATCH',
                    'Your current password does not match, please try again'
                ),
                'bad'
            );

            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        $session = $this->getRequest()->getSession();
        if (!$member) {
            if ($session->get('AutoLoginHash')) {
                $member = Member::member_from_autologinhash($session->get('AutoLoginHash'));
            }

            // The user is not logged in and no valid auto login hash is available
            if (!$member) {
                $session->clear('AutoLoginHash');

                return $this->redirect($this->addBackURLParam(Security::singleton()->Link('login')));
            }
        }

        // Check the new password
        if (empty($data['NewPassword1'])) {
            $form->sessionMessage(
                _t(
                    'SilverStripe\\Security\\Member.EMPTYNEWPASSWORD',
                    "The new password can't be empty, please try again"
                ),
                'bad'
            );

            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        // Fail if passwords do not match
        if ($data['NewPassword1'] !== $data['NewPassword2']) {
            $form->sessionMessage(
                _t(
                    'SilverStripe\\Security\\Member.ERRORNEWPASSWORD',
                    'You have entered your new password differently, try again'
                ),
                'bad'
            );

            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        // Check if the new password is accepted
        $validationResult = $member->changePassword($data['NewPassword1']);
        if (!$validationResult->isValid()) {
            $form->setSessionValidationResult($validationResult);

            return $this->redirectBackToForm();
        }

        // Clear locked out status
        $member->LockedOutUntil = null;
        $member->FailedLoginCount = null;

        // Create a successful 'LoginAttempt' as the password is reset
        if (Security::config()->get('login_recording')) {
            $loginAttempt = LoginAttempt::create();
            $loginAttempt->Status = LoginAttempt::SUCCESS;
            $loginAttempt->MemberID = $member->ID;

            if ($member->Email) {
                $loginAttempt->setEmail($member->Email);
            }

            $loginAttempt->IP = $this->getRequest()->getIP();
            $loginAttempt->write();
        }

        // Clear the members login hashes
        $member->AutoLoginHash = null;
        $member->AutoLoginExpired = DBDatetime::create()->now();
        $member->write();

        // Two factor authentication
        if (TwoFactorMemberExtension::isEnabled()) {
            if ($member->NeedTwoFactorAuth()) {
                $session = $this->getRequest()->getSession();
                $session->set('TwoFactorLoginHandler.MemberID', $member->ID);
                // Don't forget to clear this afterwards
                // Never store password
                unset($data['Password']);
                $session->set('TwoFactorLoginHandler.Data', $data);
                return $this->redirect(TwoFactorLoginHandler::getStep2Link());
            }
        }

        if ($member->canLogin()) {
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($member, false, $this->getRequest());
        }

        $session->clear('AutoLoginHash');

        // Redirect to backurl
        $backURL = $this->getBackURL();
        // Don't redirect back to itself
        $shouldRedirect = $backURL && $backURL !== Security::singleton()->Link('changepassword');
        if ($shouldRedirect) {
            return $this->redirect($backURL);
        }

        $backURL = Security::config()->get('default_reset_password_dest');
        if ($backURL) {
            return $this->redirect($backURL);
        }
        // Redirect to default location - the login form saying "You are logged in as..."
        $url = Security::singleton()->Link('login');

        return $this->redirect($url);
    }
}
