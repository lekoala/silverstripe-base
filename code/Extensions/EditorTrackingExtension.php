<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;

/**
 *
 */
class EditorTrackingExtension extends DataExtension
{
    private static $has_one = [
        "CreatedBy" => Member::class,
        "LastEditedBy" => Member::class,
    ];

    public function onBeforeWrite()
    {
        if (!$this->owner->ID) {
            $this->owner->CreatedBy = Member::currentUserID();
        }
        $this->owner->LastEditedBy = Member::currentUserID();
    }
}
