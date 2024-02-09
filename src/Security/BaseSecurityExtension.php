<?php

namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Admin\AdminRootController;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\Security;

/**
 * Additionnal functionnalities
 *
 * @property \SilverStripe\Security\Security|\LeKoala\Base\Security\BaseSecurityExtension $owner
 */
class BaseSecurityExtension extends Extension
{

    private static $allowed_actions = array(
        'end_masquerade',
        'devlogin',
        'unlock_default_admin',
    );

    public function onAfterInit()
    {
        $req = Controller::curr()->getRequest();
        $url = $req->getURL();

        $loginUrl = $this->owner->config()->login_url;
        $loginFormUrl = str_replace('/login', '/LoginForm', $loginUrl);
        $defaultLoginUrl = $loginUrl . "/default";

        // Cannot GET login form
        if ($url == $loginFormUrl && $req->isGET()) {
            header('Location: /' . ltrim($loginUrl, '/'));
            exit();
        }
        // Already logged in => no need to show log in as
        // Implement redirectIfLoggedInLink on member or on extension
        $member = Security::getCurrentUser();
        if ($member && ($loginUrl == $url || $loginFormUrl == $url || $defaultLoginUrl == $url)) {
            if ($member->hasMethod("redirectIfLoggedInLink")) {
                header('Location: ' . $member->redirectIfLoggedInLink());
                exit();
            }
        }
    }

    public function devlogin()
    {
        if (!Director::isDev()) {
            return $this->owner->httpError(404);
        }
        if (!Environment::getEnv('DEV_LOGIN_ENABLED')) {
            return $this->owner->httpError(404);
        }
        $request =  $this->owner->getRequest();
        $id = $request->getVar('id');
        $member = null;
        $redirectUrl = "/";
        if ($id) {
            if ($id == "test") {
                $testUser = Environment::getEnv('TEST_USERNAME');
                $redirectUrl = Environment::getEnv('TEST_REDIRECT_URL') ?? "/";

                $member = Member::get()->filter(['Email' => $testUser])->first();
                if (!$member->Password) {
                    $member->Password = Environment::getEnv('TEST_PASSWORD');
                    $member->write();
                }
            } else {
                $member = Member::get()->byID($id);
            }
        }
        if (!$member) {
            $member = DefaultAdminService::singleton()->findOrCreateDefaultAdmin();
            $redirectUrl = trim(AdminRootController::admin_url(), '/');
        }
        $identityStore = Injector::inst()->get(IdentityStore::class);
        $identityStore->logIn($member, true, $request);
        return $this->owner->redirect($redirectUrl);
    }

    public function unlock_default_admin()
    {
        $member = DefaultAdminService::singleton()->findOrCreateDefaultAdmin();
        if ($member->isLockedOut()) {
            $username = $this->owner->getRequest()->getVar('username');
            $password = $this->owner->getRequest()->getVar('password');
            $check = DefaultAdminService::isDefaultAdminCredentials($username, $password);
            if ($check) {
                return $member->doUnlock();
            }
            return 'Invalid login/password';
        }
        return 'Admin is not locked';
    }

    public function end_masquerade()
    {
        /* @var $session \SilverStripe\Control\Session */
        $session = $this->owner->getRequest()->getSession();

        // We have a masquerade
        if ($session->get('Masquerade.Old.loggedInAs')) {
            $backURL = $session->get('Masquerade.BackURL');

            // Most of the time, masquerade is made from the admin
            if (!$backURL) {
                $adminURL = AdminRootController::get_admin_route();
                $backURL = '/' . $adminURL;
            }
            $oldSession = $session->get('Masquerade.Old');
            $session->clearAll();
            foreach ($oldSession as $name => $val) {
                $session->set($name, $val);
            }
            return $this->owner->redirect($backURL);
        }

        // Do a regular logout
        $this->owner->logout(false);
        return $this->owner->redirect('/');
    }
}
