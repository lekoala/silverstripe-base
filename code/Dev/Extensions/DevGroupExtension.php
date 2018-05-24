<?php

namespace LeKoala\Base\Dev\Extensions;

use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use SilverStripe\View\Parsers\URLSegmentFilter;

class DevGroupExtension extends DataExtension
{
    public function onAfterWrite()
    {
        // Always populate a group with a user
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
