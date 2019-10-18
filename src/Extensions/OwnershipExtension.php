<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;

/**
 * Make a DataObject have a Member owner
 */
class OwnershipExtension extends DataExtension
{
    private static $has_one = [
        "Owner" => Member::class,
    ];

    public function onBeforeWrite()
    {
        // Make sure we have a owner
        if (!$this->owner->OwnerID) {
            $this->owner->OwnerID = Member::currentUserID();
        }
    }

    public function IsOwner()
    {
        return $this->owner->OwnerID == Member::currentUserID();
    }

    public function IsNotOwner()
    {
        return $this->owner->OwnerID != Member::currentUserID();
    }
}
