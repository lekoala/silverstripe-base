<?php
namespace LeKoala\Base\Security;

use SilverStripe\Control\Controller;

/**
 * Add extensions point for MemberAuthenticator
 *
 * forgotPassword is on LostPasswordHandler and NOT on the member class
 */
trait MemberAuthenticatorExtensions
{

    /**
     * This is only called if Security::login_recording is set to true
     *
     * @return void
     */
    public function authenticationSucceeded()
    {
    }

    /**
     * This is only called if Security::login_recording is set to true
     *
     * @param array $data
     * @param HTTPRequest $request
     * @return void
     */
    public function authenticationFailed($data, $request)
    {
    }

    /**
     * This is only called if Security::login_recording is set to true
     *
     * @param array $data
     * @param HTTPRequest $request
     * @return void
     */
    public function authenticationFailedUnknownUser($data, $request)
    {
    }
}
