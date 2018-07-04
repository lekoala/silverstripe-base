<?php
namespace LeKoala\Base\Security;

use SilverStripe\Control\Controller;

trait MasqueradeMember
{
    /**
     * Impersonate that specific user and redirect to home page
     *
     * @return void
     */
    public function doLoginAs()
    {
        $this->masqueradeSession();
        $controller = Controller::curr();
        $request = $controller->getRequest();
        $session = $request->getSession();
        $session->save($request);

        //TODO: properly use ss response object
        header('Location: /');
        exit();
    }

    /**
     * Impersonate a user
     *
     * @return void
     */
    public function masqueradeSession()
    {
        $controller = Controller::curr();
        $request = $controller->getRequest();
        $session = $request->getSession();
        $sessionData = $session->getAll();
        $session->clearAll();
        $session->set("loggedInAs", $this->getOwner()->ID);
        $session->set('Masquerade.Old', $sessionData);
        $session->set('Masquerade.BackURL', $controller->getReferer());
    }

    /**
     * @return boolean
     */
    public function IsMasquerading()
    {
        return Controller::curr()->getRequest()->getSession()->get('Masquerade.Old');
    }
}
