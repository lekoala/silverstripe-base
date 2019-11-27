<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;

/**
 * Make a DataObject have a Member owner
 *
 * @property \LeKoala\Base\Extensions\OwnershipExtension $owner
 * @property int $OwnerID
 * @method \SilverStripe\Security\Member Owner()
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

    /**
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        // Can always view
        if ($this->IsOwner()) {
            return true;
        }
        return Permission::check('ADMIN', 'any', $member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        // Can always view
        if ($this->IsOwner()) {
            return true;
        }
        return Permission::check('ADMIN', 'any', $member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        // Can always view
        if ($this->IsOwner()) {
            return true;
        }
        return Permission::check('ADMIN', 'any', $member);
    }
}
