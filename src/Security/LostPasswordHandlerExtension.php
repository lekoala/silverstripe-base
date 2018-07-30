<?php
namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;

/**
 * Class \LeKoala\Base\Security\LostPasswordHandlerExtension
 *
 * @property \SilverStripe\Security\MemberAuthenticator\LostPasswordHandler|\LeKoala\Base\Security\LostPasswordHandlerExtension $owner
 */
class LostPasswordHandlerExtension extends Extension
{

    /**
     * Allow vetoing forgot password requests
     *
     * Results are passed to this
     *
     *  if ($results && is_array($results) && in_array(false, $results, true)) {
     *      return $this->redirectToLostPassword();
     *  }
     *
     * @param Member $member
     * @return array
     */
    public function forgotPassword($member)
    {
    }
}
