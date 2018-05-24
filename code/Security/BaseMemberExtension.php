<?php
namespace LeKoala\Base\Security;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;

/**
 */
class BaseMemberExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        // Some fields don't make sense upon creation
        if (!$this->owner->ID) {
            $fields->removeByName('FailedLoginCount');
        }
        // Some fields required ADMIN rights
        if (!Permission::check('ADMIN')) {
            $fields->removeByName('FailedLoginCount');
        }
    }
    /**
     * @param array $extraIDs
     * @return Member[]
     */
    public static function getMembersFromSecurityGroups($extraIDs = [])
    {
        $sql = 'SELECT DISTINCT MemberID FROM Group_Members INNER JOIN Permission ON Permission.GroupID = Group_Members.GroupID WHERE Code LIKE \'CMS_%\' OR Code = \'ADMIN\'';
        $ids = array_merge(DB::query($sql)->column(), $extraIDs);
        return Member::get()->filter('ID', $ids);
    }


    /**
     * @return string
     */
    public function DirectGroupsList()
    {
        return implode(',', $this->owner->DirectGroups()->column('Title'));
    }
}
