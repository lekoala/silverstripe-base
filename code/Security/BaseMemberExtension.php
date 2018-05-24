<?php
namespace LeKoala\Base\Security;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;

/**
 */
class BaseMemberExtension extends DataExtension
{
    /**
     * @return Member[]
     */
    public static function getMembersFromSecurityGroups()
    {
        $sql = 'SELECT DISTINCT MemberID FROM Group_Members INNER JOIN Permission ON Permission.GroupID = Group_Members.GroupID WHERE Code LIKE \'CMS_%\' OR Code = \'ADMIN\'';
        $ids = DB::query($sql)->column();
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
