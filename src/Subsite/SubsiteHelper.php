<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;

/**
 * Helps providing base functionnalities where including
 * subsite module is optional and yet provide a consistent api
 */
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
     * @return bool
     */
    public static function subsiteFilterDisabled()
    {
        if (!self::usesSubsite()) {
            return true;
        }
        return Subsite::$disable_subsite_filter;
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
        $session = Controller::curr()->getRequest()->getSession();
        if ($session) {
            return $session->get('SubsiteID');
        }
        return 0;
    }

    /**
     * Typically call this on PageController::init
     * This is due to InitStateMiddleware not using session in front end and not persisting get var parameters
     *
     * @param HTTPRequest $request
     * @return int
     */
    public static function forceSubsiteFromRequest(HTTPRequest $request)
    {
        $subsiteID = $request->getVar('SubsiteID');
        if ($subsiteID) {
            $request->getSession()->set('ForcedSubsiteID', $subsiteID);
        } else {
            $subsiteID = $request->getSession()->get('ForcedSubsiteID');
        }
        if ($subsiteID) {
            self::changeSubsite($subsiteID, true);
        }
        return $subsiteID;
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

        // Do this otherwise changeSubsite has no effect if false
        SubsiteState::singleton()->setUseSessions(true);
        Subsite::changeSubsite($ID);
        // This can help avoiding getting static objects like SiteConfig
        if ($flush !== null && $flush) {
            DataObject::reset();
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
     * @return array
     */
    public static function listSubsites()
    {
        if (!self::usesSubsite()) {
            return [];
        }
        return  Subsite::get()->map();
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
