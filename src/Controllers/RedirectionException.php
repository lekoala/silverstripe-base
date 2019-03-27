<?php

namespace LeKoala\Base\Controllers;

use Exception;

/**
 * Redirect to a given url and interrupts the flow
 *
 * This is meant to be catched in your controller (see ImprovedActions::handleAction)
 */
class RedirectionException extends Exception
{
    protected $redirectUrl = null;

    public function __construct($redirectUrl = null, $code = 307)
    {
        $this->redirectUrl = $redirectUrl;
        $exceptionMessage = "Redirected to $redirectUrl";
        parent::__construct($exceptionMessage, $code);
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }
}
