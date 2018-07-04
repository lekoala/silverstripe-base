<?php
namespace LeKoala\Base\Security;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Security\Permission;
use LeKoala\Base\Security\MemberAudit;
use LeKoala\Base\Actions\CustomAction;

/**
 */
class BaseMemberExtension extends DataExtension
{
    use MasqueradeMember;

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

    public function updateCMSActions(FieldList $actions)
    {
        // Admin can unlock people
        if (Permission::check('ADMIN') && $this->owner->isLockedOut()) {
            $actions->push($doUnlock = new CustomAction('doUnlock', 'Unlock'));
        }

        // Login as (but cannot login as yourself :-) )
        if (Permission::check('ADMIN') && $this->owner->ID != Member::currentUserID()) {
            $actions->push($doLoginAs = new CustomAction('doLoginAs', 'Login as'));
        }
    }

    public function doUnlock()
    {
        $attempts = LoginAttempt::get()->filter($filter = array(
            'Email' => $this->owner->Email
        ))->sort('Created', 'DESC')->exclude('Status', 'Success')->limit(10);

        foreach ($attempts as $attempt) {
            $attempt->delete();
        }

        $this->owner->LockedOutUntil = null;
        $this->owner->write();

        return 'Member unlocked';
    }

    /**
     * @return boolean
     */
    public function NotMe()
    {
        return $this->owner->ID !== Member::currentUserID();
    }

    /**
     * @return boolean
     */
    public function IsAdmin()
    {
        return Permission::check('CMS_ACCESS');
    }


    /**
     * @param string $event
     * @param string $data
     * @return int
     */
    public function audit($event, $data = null)
    {
        $r = new MemberAudit;
        $r->MemberID = $this->owner->ID;
        $r->Event = $event;
        if ($data) {
            $r->AuditData = $data;
        }
        return $r->write();
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
