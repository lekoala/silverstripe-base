<?php

namespace LeKoala\Base\Security;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Security\Permission;
use LeKoala\Base\Actions\CustomAction;
use LeKoala\Base\Security\MemberAudit;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\LoginAttempt;
use LeKoala\Base\Extensions\ValidationStatusExtension;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * A lot of base functionalities for your members
 *
 * Most group of functions are grouped within traits when possible
 *
 * @link https://docs.silverstripe.org/en/4/developer_guides/extending/how_tos/track_member_logins/
 * @property \SilverStripe\Security\Member|\LeKoala\Base\Security\BaseMemberExtension $owner
 * @property string $LastVisited
 * @property int $NumVisit
 */
class BaseMemberExtension extends DataExtension
{
    use MasqueradeMember;
    use MemberAuthenticatorExtensions;

    private static $db = [
        'LastVisited' => 'Datetime',
        'NumVisit' => 'Int',
    ];
    private static $has_many = [
        "Audits" => MemberAudit::class . ".Member",
    ];

    public function canLogIn(ValidationResult $result)
    {
        // Admin can always log in
        if (Permission::check('ADMIN', 'any', $this->owner)) {
            return;
        }
        // If MemberValidationStatus extension is applied, check validation status
        if ($this->owner->hasExtension(ValidationStatusExtension::class)) {
            if ($this->owner->IsValidationStatusPending()) {
                $result->addError(_t('BaseMemberExtension.ACCOUNT_PENDING', "Your account is currently pending"));
            }
            if ($this->owner->IsValidationStatusDisabled()) {
                $result->addError(_t('BaseMemberExtension.ACCOUNT_DISABLED', "Your account has been disabled"));
            }
        }
    }

    /**
     * @deprecated
     */
    public function beforeMemberLoggedIn()
    {
        //
    }

    public function afterMemberLoggedIn()
    {
        $this->logVisit();
    }

    /**
     * Called by CookieAuthenticationHandler
     */
    public function memberAutoLoggedIn()
    {
        $this->logVisit();
    }

    public function beforeMemberLoggedOut($request)
    {
        //
    }

    public function afterMemberLoggedOut($request)
    {
        //
    }

    /**
     * Returns the fields for the member form - used in the registration/profile module.
     * It should return fields that are editable by the admin and the logged-in user.
     *
     * @param FieldList $fields
     */
    public function updateMemberFormFields(FieldList $fields)
    {
        //
    }

    public function updateMemberPasswordField($password)
    {
        //
    }

    public function updateDateFormat($format)
    {
        //
    }

    public function updateTimeFormat($format)
    {
        //
    }

    public function updateGroups($groups)
    {
        //
    }

    public function onBeforeChangePassword($password, $valid)
    {
        //
    }

    public function onAfterChangePassword($password, $valid)
    {
        //
    }

    public function registerFailedLogin()
    {
        //
    }

    public function updateCMSFields(FieldList $fields)
    {
        $ctrl = Controller::curr();

        $fields->makeFieldReadonly([
            'LastVisited',
            'NumVisit',
        ]);

        $Audits = $fields->dataFieldByName('Audits');
        if ($Audits) {
            if (Permission::check('ADMIN')) {
                $AuditsConfig = $Audits->getConfig();
                $AuditsConfig->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
                $AuditsConfig->removeComponentsByType(GridFieldAddNewButton::class);
            } else {
                $fields->removeByName('Audits');
            }
        }

        // Some fields don't make sense upon creation
        if (!$this->owner->ID) {
            $fields->removeByName(
                [
                    'FailedLoginCount',
                    'LastVisited',
                    'NumVisit',
                ]
            );
        }
        // Some fields required ADMIN rights
        if (!Permission::check('ADMIN')) {
            $fields->removeByName('FailedLoginCount');
        }
        // Some things should never be shown outside of SecurityAdmin
        if (get_class($ctrl) != SecurityAdmin::class) {
            $fields->removeByName([
                'DirectGroups',
                'Permissions',
                'LastVisited',
                'NumVisit',
            ]);
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
        $lastSuccess = LoginAttempt::get()->filter($filter = array(
            'MemberID' => $this->owner->ID
        ))->sort('Created', 'DESC')->filter('Status', 'Success')->first();

        $sql = 'DELETE FROM LoginAttempt WHERE MemberID = ? AND Status = ?';
        $params = [
            $this->owner->ID,
            'Failure'
        ];
        if ($lastSuccess) {
            $sql .= ' AND ID > ?';
            $params[] = $lastSuccess->ID;
        }

        // Cleanup failure attempt
        DB::prepared_query($sql, $params);

        try {
            $this->owner->LockedOutUntil = null;
            $this->owner->write();

            $msg = _t('BaseMemberExtension.MEMBER_UNLOCKED', 'Member unlocked');
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
        }
        return $msg;
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
        return Permission::check('CMS_ACCESS', 'any', $this->owner);
    }

    /**
     * @param string $event
     * @param string|array $data
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

    protected function logVisit()
    {
        if (!Security::database_is_ready()) {
            return;
        }

        DB::query(sprintf(
            'UPDATE "Member" SET "LastVisited" = %s, "NumVisit" = "NumVisit" + 1 WHERE "ID" = %d',
            DB::get_conn()->now(),
            $this->owner->ID
        ));
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
