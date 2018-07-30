<?php

namespace LeKoala\Base\Dev\Extensions;

use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use SilverStripe\View\Parsers\URLSegmentFilter;
use SilverStripe\Control\Controller;
use SilverStripe\Admin\LeftAndMain;

/**
 * Class \LeKoala\Base\Dev\Extensions\DevGroupExtension
 *
 * @property \LeKoala\Base\Dev\Extensions\DevGroupExtension $owner
 */
class DevGroupExtension extends DataExtension
{
    public function onAfterWrite()
    {
        $ctrl = Controller::curr();
        if (!$ctrl instanceof LeftAndMain) {
            return;
        }
        // Always populate a group with a user
        // ! this should not run during test or it causes infinite loops
        if ($this->owner->Members()->count() == 0) {
            $groupTitle = $this->owner->Title;
            $filter = new URLSegmentFilter;

            $defaultAdmin = Security::findAnAdministrator();
            $emailParts = explode('@', $defaultAdmin->Email);

            // Let's create a fake member for this
            $member = Member::create();
            $member->Email = $filter->filter($groupTitle) . '@' . $emailParts[1];
            $member->FirstName = 'Default User';
            $member->Surname = $groupTitle;
            $member->write();

            $member->changePassword('Test0000');
            $member->write();

            $this->owner->Members()->add($member);
        }
    }
}
