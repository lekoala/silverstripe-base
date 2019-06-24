<?php
namespace LeKoala\Base\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;

/**
 * Controls masquerade related functions
 *
 * Remember you can visit /Security/end_masquerade to go back to your previous user
 */
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

        $response = new HTTPResponse();
        $response->addHeader("X-ControllerURL", "/");
        $response->addHeader("X-Reload", true);
        $response->redirect('/');
        return $response;
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
        $this->owner->extend('onMasquerade', $session);
    }

    /**
     * @return boolean
     */
    public function IsMasquerading()
    {
        return Controller::curr()->getRequest()->getSession()->get('Masquerade.Old');
    }
}
