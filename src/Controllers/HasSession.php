<?php

namespace LeKoala\Base\Controllers;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;

/**
 * Trait add a static getter. We use static because we don't now if we have a context
 * and we don't use instance anyway
 *
 * @link https://docs.silverstripe.org/en/4/developer_guides/cookies_and_sessions/sessions/
 */
trait HasSession
{
    /**
     * @return Session
     */
    public static function getSession()
    {
        if (Controller::has_curr()) {
            $ctrl = Controller::curr();
            $request = $ctrl->getRequest();
            try {
                $session = $request->getSession();
            } catch (Exception $ex) {
                $session = null;
            }
        }
        if (!$session) {
            $data = isset($_SESSION) ? $_SESSION : [];
            $session = new Session($data);
        }
        return $session;
    }
}
