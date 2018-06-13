<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\Subsites\State\SubsiteState;
use SilverStripe\Subsites\Model\Subsite;

class SubsiteHelper
{
    /**
     * @var boolean
     */
    protected static $previousState;

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

    public static function EnableFilter()
    {
        if (!self::UsesSubsite()) {
            return;
        }
        self::$previousState = Subsite::$disable_subsite_filter;
        Subsite::$disable_subsite_filter = false;
    }

    public static function DisableFilter()
    {
        if (!self::UsesSubsite()) {
            return;
        }
        self::$previousState = Subsite::$disable_subsite_filter;
        Subsite::$disable_subsite_filter = true;
    }

    public static function RestoreFilter()
    {
        if (!self::UsesSubsite()) {
            return;
        }
        Subsite::$disable_subsite_filter = self::$previousState;
    }
}
