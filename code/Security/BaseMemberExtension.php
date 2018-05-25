<?php
namespace LeKoala\Base\Security;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Admin\SecurityAdmin;

/**
 */
class BaseMemberExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $ctrl = Controller::curr();
        // Some fields don't make sense upon creation
        if (!$this->owner->ID) {
            $fields->removeByName('FailedLoginCount');
        }
        // Some fields required ADMIN rights
        if (!Permission::check('ADMIN')) {
            $fields->removeByName('FailedLoginCount');
        }
        // Some things should never be shown outside of SecurityAdmin
        if (get_class($ctrl) != SecurityAdmin::class) {
            $fields->removeByName('DirectGroups');
            $fields->removeByName('Permissions');
        }
    }

    /**
     * @return array
     */
    public static function getMembersFromSecurityGroupsIDs()
    {
        $sql = 'SELECT DISTINCT MemberID FROM Group_Members INNER JOIN Permission ON Permission.GroupID = Group_Members.GroupID WHERE Code LIKE \'CMS_%\' OR Code = \'ADMIN\'';
        return DB::query($sql)->column();
    }

    /**
     * @param array $extraIDs
     * @return Member[]
     */
    public static function getMembersFromSecurityGroups($extraIDs = [])
    {
        $ids = array_merge(self::getMembersFromSecurityGroupsIDs(), $extraIDs);
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
