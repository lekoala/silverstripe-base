<?php
namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;

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
