<?php

namespace LeKoala\Base\Security;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use LeKoala\Base\Controllers\HasLogger;
use LeKoala\MemberAudit\MemberAuditExtension;
use SilverStripe\Control\Director;
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
     * @param ?Member $member
     * @return void
     */
    public function forgotPassword(&$member)
    {
        // Attempt on invalid member
        if (!$member) {
            $email = $_POST['Email'] ?? '(undefined email)';
            self::getLogger()->debug("Invalid member " . $email);
            return;
        }
        // Default admin cannot reset
        $username = DefaultAdminService::getDefaultAdminUsername();
        if ($member->Email == $username && Director::isLive()) {
            self::getLogger()->debug("Default admin cannot reset his password");
            $member = null;
            return;
        }
        if ($member->hasExtension(MemberAuditExtension::class) && Director::isLive()) {
            /** @var Member&MemberAuditExtension $member */
            // Avoid hammering / 2 min per request
            $lastAudit = $member->Audits()->filter('Event', 'password_requested')->first();
            if ($lastAudit && strtotime($lastAudit->Created) >= strtotime('-2 minutes')) {
                self::getLogger()->debug("Avoid hammering for #" . $member->ID);
                $member = null;
                return;
            }

            $member->audit('password_requested');
        }
    }
}
