<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\Subsites\State\SubsiteState;

class SubsiteHelper
{
    public static function CurrentSubsiteID()
    {
        if (self::UsesSubsite()) {
            return SubsiteState::singleton()->getSubsiteId();
        }
        return 0;
    }

    public static function UsesSubsite()
    {
        return class_exists(SubsiteState::class);
    }
}
