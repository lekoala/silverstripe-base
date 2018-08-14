<?php
namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;
use SilverStripe\Admin\AdminRootController;

/**
 * Additionnal functionnalities
 */
class BaseSecurityExtension extends Extension
{

    private static $allowed_actions = array(
        'end_masquerade'
    );

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
