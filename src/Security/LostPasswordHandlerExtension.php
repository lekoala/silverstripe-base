<?php

namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;
use LeKoala\Base\Controllers\HasLogger;
use SilverStripe\Security\DefaultAdminService;

/**
 * Class \LeKoala\Base\Security\LostPasswordHandlerExtension
 *
 * @property \SilverStripe\Security\MemberAuthenticator\LostPasswordHandler|\LeKoala\Base\Security\LostPasswordHandlerExtension $owner
 */
class LostPasswordHandlerExtension extends Extension
{
    use HasLogger;

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
        // Attempt on invalid member
        if (!$member) {
            self::getLogger()->debug("Invalid member " . $_POST['Email'] ?? '(undefined email)');
            return;
        }
        // Default admin cannot reset
        $username = DefaultAdminService::getDefaultAdminUsername();
        if ($member->Email == $username) {
            self::getLogger()->debug("Default admin cannot reset his password");
            return;
        }
        // Avoid hammering / 2 min per request
        $latestedAudit = $member->Audits()->filter('Event', 'password_requested')->first();
        if ($latestedAudit && strtotime($latestedAudit->Created) >= strtotime('-2 minutes')) {
            self::getLogger()->debug("Avoid hammering for #" . $member->ID);
            return false;
        }
        $member->audit('password_requested');
    }
}
