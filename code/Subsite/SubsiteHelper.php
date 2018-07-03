<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\Subsites\State\SubsiteState;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\ORM\DataObject;

class SubsiteHelper
{
    /**
     * @var boolean
     */
    protected static $previousState;

    /**
     * Return current subsite id (even if module is not installed, which returns 0)
     *
     * @return int
     */
    public static function CurrentSubsiteID()
    {
        if (self::UsesSubsite()) {
            return SubsiteState::singleton()->getSubsiteId();
        }
        return 0;
    }

    /**
     * @return Subsite
     */
    public static function CurrentSubsite()
    {
        $id = self::CurrentSubsiteID();
        if (self::UsesSubsite()) {
            return DataObject::get_by_id(Subsite::class, $id);
        }
        return false;
    }

    /**
     * Do we have the subsite module installed
     * TODO: check if it might be better to use module manifest instead?
     *
     * @return bool
     */
    public static function UsesSubsite()
    {
        return class_exists(SubsiteState::class);
    }

    /**
     * Enable subsite filter and store previous state
     *
     * @return void
     */
    public static function EnableFilter()
    {
        if (!self::UsesSubsite()) {
            return;
        }
        self::$previousState = Subsite::$disable_subsite_filter;
        Subsite::$disable_subsite_filter = false;
    }

    /**
     * Disable subsite filter and store previous state
     *
     * @return void
     */
    public static function DisableFilter()
    {
        if (!self::UsesSubsite()) {
            return;
        }
        self::$previousState = Subsite::$disable_subsite_filter;
        Subsite::$disable_subsite_filter = true;
    }

    /**
     * Restore subsite filter based on previous set (set when called EnableFilter or DisableFilter)
     */
    public static function RestoreFilter()
    {
        if (!self::UsesSubsite()) {
            return;
        }
        Subsite::$disable_subsite_filter = self::$previousState;
    }

    /**
     * Execute the callback in given subsite
     *
     * @param int $ID Subsite ID or 0 for main site
     * @param callable $cb
     * @return void
     */
    public static function withSubsite($ID, $cb)
    {
        $currentID = self::CurrentSubsiteID();
        SubsiteState::singleton()->setSubsiteId($ID);
        $cb();
        SubsiteState::singleton()->setSubsiteId($currentID);
    }

    /**
     * Execute the callback in all subsites
     *
     * @param callable $cb
     * @param bool $încludeMainSite
     * @return void
     */
    public static function withSubsites($cb, $includeMainSite = true)
    {
        if (!self::UsesSubsite()) {
            $cb();
            return;
        }

        if ($includeMainSite) {
            SubsiteState::singleton()->setSubsiteId(0);
            $cb(0);
        }

        $currentID = self::CurrentSubsiteID();
        $subsites = Subsite::get();
        foreach ($subsites as $subsite) {
            // TODO: maybe use changeSubsite instead?
            SubsiteState::singleton()->setSubsiteId($subsite->ID);
            $cb($subsite->ID);
        }
        SubsiteState::singleton()->setSubsiteId($currentID);
    }
}
