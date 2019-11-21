<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;

/**
 * Class \LeKoala\Base\Extensions\EditorTrackingExtension
 *
 * @property \LeKoala\Base\Extensions\EditorTrackingExtension $owner
 * @property int $CreatedByID
 * @property int $LastEditedByID
 * @method \SilverStripe\Security\Member CreatedBy()
 * @method \SilverStripe\Security\Member LastEditedBy()
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
