<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\Subsites\State\SubsiteState;

class SubsiteHelper
{
    public static function CurrentSubsiteID()
    {
        if (class_exists(SubsiteState::class)) {
            return SubsiteState::singleton()->getSubsiteId();
        }
        return 0;
    }
}
