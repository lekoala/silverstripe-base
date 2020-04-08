<?php

namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;
use SilverStripe\Admin\AdminRootController;
use SilverStripe\Control\Controller;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\Member;

/**
 * Additionnal functionnalities
 *
 * @property \SilverStripe\Security\CMSSecurity|\SilverStripe\Security\Security|\LeKoala\Base\Security\BaseSecurityExtension $owner
 */
class BaseSecurityExtension extends Extension
{

    private static $allowed_actions = array(
        'end_masquerade',
        'unlock_default_admin',
    );

    public function onAfterInit()
    {
        $req = Controller::curr()->getRequest();
        $url = $req->getURL();

        $loginUrl = $this->owner->config()->login_url;
        $loginFormUrl = str_replace('/login', '/LoginForm', $loginUrl);

        // Cannot GET login form
        if ($url == $loginFormUrl && $req->isGET()) {
            header('Location: /' . $loginUrl);
            exit();
        }
        // Already logged in
        // if (Member::currentUserID() && ($loginUrl == $url || $loginFormUrl == $url)) {
        //     header('Location: /');
        //     exit();
        // }
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
