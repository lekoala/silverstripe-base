<?php

namespace LeKoala\Base\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;

/**
 * Controls masquerade related functions
 *
 * Remember you can visit /Security/end_masquerade to go back to your previous user
 * Use $LogoutURL from BaseContentController to get accurate url for easier use
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
        $response->addHeader("X-ControllerURL", "/home");
        $response->addHeader("X-Reload", true);
        // don't use redirect, but rely on X-ControllerURL instead to have proper redirect from cms
        // $response->redirect('/');
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

        /** @var IdentityStore $identityStore */
        $identityStore = Injector::inst()->get(IdentityStore::class);
        $identityStore->logIn($this->getOwner(), false, $request);

        // variable is configurable in framework/_config/security.yml
        // SilverStripe\Security\MemberAuthenticator\SessionAuthenticationHandler:
        //   SessionVariable: loggedInAs
        // $session->set("loggedInAs", $this->getOwner()->ID);

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
