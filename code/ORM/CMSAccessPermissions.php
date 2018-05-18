<?php

namespace LeKoala\Base\ORM;

use SilverStripe\Security\Permission;

/**
 * Simple trait to make can permissions dependent on CMS_ACCESS
 */
trait CMSAccessPermissions
{
    public function canView($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canEdit($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canCreate($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canDelete($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS', 'any', $member);
    }
}
