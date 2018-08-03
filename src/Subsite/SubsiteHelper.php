<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Controller;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;

class SubsiteHelper
{
    /**
     * @var boolean
     */
    protected static $previousState;

    /**
     * @var int
     */
    protected static $previousSubsite;

    /**
     * Return current subsite id (even if module is not installed, which returns 0)
     *
     * @return int
     */
    public static function currentSubsiteID()
    {
        if (self::usesSubsite()) {
            return SubsiteState::singleton()->getSubsiteId();
        }
        return 0;
    }

    /**
     * @return Subsite
     */
    public static function currentSubsite()
    {
        $id = self::currentSubsiteID();
        if (self::usesSubsite()) {
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
    public static function usesSubsite()
    {
        return class_exists(SubsiteState::class);
    }

    /**
     * Enable subsite filter and store previous state
     *
     * @return void
     */
    public static function enableFilter()
    {
        if (!self::usesSubsite()) {
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
    public static function disableFilter()
    {
        if (!self::usesSubsite()) {
            return;
        }
        self::$previousState = Subsite::$disable_subsite_filter;
        Subsite::$disable_subsite_filter = true;
    }

    /**
     * Restore subsite filter based on previous set (set when called enableFilter or disableFilter)
     */
    public static function restoreFilter()
    {
        if (!self::usesSubsite()) {
            return;
        }
        Subsite::$disable_subsite_filter = self::$previousState;
    }

    /**
     * @return int
     */
    public static function SubsiteIDFromSession()
    {
        return Controller::curr()->getRequest()->getSession()->get('SubsiteID');
    }

    /**
     * @param string $ID
     * @param bool $flush
     * @return void
     */
    public static function changeSubsite($ID, $flush = null)
    {
        if (!self::usesSubsite()) {
            return;
        }
        self::$previousSubsite = self::currentSubsiteID();
        Subsite::changeSubsite($ID);
        // This can help avoiding getting static objects like SiteConfig
        if ($flush !== null) {
            DataObject::flushCache();
        }
    }

    /**
     * @return void
     */
    public static function restoreSubsite()
    {
        if (!self::usesSubsite()) {
            return;
        }
        Subsite::changeSubsite(self::$previousSubsite);
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
        $currentID = self::currentSubsiteID();
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
        if (!self::usesSubsite()) {
            $cb();
            return;
        }

        if ($includeMainSite) {
            SubsiteState::singleton()->setSubsiteId(0);
            $cb(0);
        }

        $currentID = self::currentSubsiteID();
        $subsites = Subsite::get();
        foreach ($subsites as $subsite) {
            // TODO: maybe use changeSubsite instead?
            SubsiteState::singleton()->setSubsiteId($subsite->ID);
            $cb($subsite->ID);
        }
        SubsiteState::singleton()->setSubsiteId($currentID);
    }
}
