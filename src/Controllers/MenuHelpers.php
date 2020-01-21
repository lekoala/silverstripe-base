<?php

namespace LeKoala\Base\Controllers;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\CMS\Model\SiteTree;

trait MenuHelpers
{
    /**
     * @param DataList $result
     * @return ArrayList
     */
    protected function filterVisibleMenuItems($result)
    {
        $visible = [];

        if (empty($result)) {
            return $visible;
        }

        // Remove all entries the can not be viewed by the current user
        foreach ($result as $page) {
            /** @var SiteTree $page */
            if ($page->canView()) {
                $visible[] = $page;
            }
        }

        return new ArrayList($visible);
    }

    /**
     * You can include FooterMenu to use this
     *
     * @return DataList
     */
    public function FooterMenu()
    {
        $result = SiteTree::get()->filter([
            "ShowInFooter" => 1,
        ]);

        return $this->filterVisibleMenuItems($result);
    }

    /**
     * @return DataList
     */
    public function MembersOnlyMenu()
    {
        $result = SiteTree::get()->filter([
            "ShowInMenus" => 1,
            "ParentID" => 0,
            "CanViewType" => "LoggedInUsers"
        ]);

        return $result;
    }

    /**
     * @return DataList
     */
    public function NonMemberOnlyMenu()
    {
        $result = SiteTree::get()->filter([
            "ShowInMenus" => 1,
            "ParentID" => 0,
            "CanViewType" => ["Anyone", "Inherit"]
        ]);

        return $result;
    }

    /**
     * This function is useful if you display two sets of menus
     * one for your logged in users and one for non logged in users
     * @return ArrayList
     */
    public function ToggleMemberMenu()
    {
        if (Member::currentUserID()) {
            $result = $this->MembersOnlyMenu();
        } else {
            $result = $this->NonMemberOnlyMenu();
        }

        return  $this->filterVisibleMenuItems($result);
    }
}
