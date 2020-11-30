<?php

namespace LeKoala\Base\Security;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use LeKoala\Base\Helpers\IPHelper;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Security\Permission;
use LeKoala\Base\Actions\CustomAction;
use LeKoala\Base\Security\MemberAudit;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\IdentityStore;
use LeKoala\Base\Security\BaseAuthenticator;
use LeKoala\Base\Extensions\ValidationStatusExtension;
use SilverStripe\Control\Director;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
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
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Security\MemberAudit[] Audits()
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

    /**
     * @var boolean
     */
    public static $do_base_member_fields_update = true;

    /**
     * @return string
     */
    public function Fullname()
    {
        return trim($this->owner->FirstName . ' ' . $this->owner->Surname);
    }

    /**
     * @return string
     */
    public function getPasswordResetLink()
    {
        $token = $this->owner->generateAutologinTokenAndStoreHash();
        return Director::absoluteURL(Security::getPasswordResetLink($this->owner, $token));
    }

    /**
     * @return boolean
     */
    public function NeedTwoFactorAuth()
    {
        //2fa is disabled globally
        if (!BaseAuthenticator::is2FAenabled()) {
            return false;
        }
        // the ip is whitelisted
        $adminIps = Security::config()->admin_ip_whitelist;
        if (!empty($adminIps)) {
            $requestIp = Controller::curr()->getRequest()->getIP();
            if (IPHelper::checkIp($requestIp, $adminIps)) {
                return false;
            }
        }
        // we only required 2fa for admins
        if (BaseAuthenticator::is2FAenabledAdminOnly()) {
            return Permission::check('CMS_ACCESS', 'any', $this->owner);
        }
        return true;
    }

    /**
     * @return array
     */
    public function AvailableTwoFactorMethod()
    {
        $arr = [];
        if ($this->owner->Mobile) {
            $arr[] = 'text_message';
        }
        if ($this->owner->TOTPToken) {
            $arr[] = 'totp';
        }
        return $arr;
    }

    /**
     * @return string text_message, totp
     */
    public function PreferredTwoFactorAuth()
    {
        $arr = $this->AvailableTwoFactorMethod();
        if (!empty($arr)) {
            return $arr[0];
        }
        return false;
    }

    /**
     * This is called by Member::validateCanLogin which is typically called in MemberAuthenticator::authenticate::authenticateMember
     * which is used in LoginHandler::doLogin::checkLogin
     *
     * This means canLogIn is called before 2FA, for instance
     *
     * @param ValidationResult $result
     * @return void
     */
    public function canLogIn(ValidationResult $result)
    {
        // Ip whitelist for users with cms access (empty by default)
        // SilverStripe\Security\Security:
        //   admin_ip_whitelist:
        //     - 127.0.0.1/255
        $adminIps = Security::config()->admin_ip_whitelist;
        $need2Fa = $this->NeedTwoFactorAuth();
        $hasTwoFaMethods = count($this->AvailableTwoFactorMethod()) > 0;
        if (!empty($adminIps)) {
            $requestIp = Controller::curr()->getRequest()->getIP();
            $isCmsUser = Permission::check('CMS_Access', 'any', $this->owner);
            if ($isCmsUser && !IPHelper::checkIp($requestIp, $adminIps)) {
                // No two fa method to validate important account
                if (!$hasTwoFaMethods) {
                    $this->owner->audit('invalid_ip_admin', ['ip' => $requestIp]);
                    $result->addError(_t('BaseMemberExtension.ADMIN_IP_INVALID', "Your ip address {address} is not whitelisted for this account level", ['address' => $requestIp]));
                }
            } else {
                // User has been whitelisted, no need for 2fa
                if (Config::inst()->get(BaseAuthenticator::class, 'disable_2fa_whitelisted_ips')) {
                    $need2Fa = false;
                }
            }
        }

        // Member need two factor auth but has no available method
        if ($need2Fa && !$hasTwoFaMethods) {
            $result->addError(_t('BaseMemberExtension.YOU_NEED_2FA_METHOD', 'Your account needs two factor auth but does not have any available authentication method'));
        }

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
        if (!self::$do_base_member_fields_update) {
            return;
        }

        $ctrl = Controller::curr();

        $fields->makeFieldReadonly([
            'FailedLoginCount',
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
                ]
            );
        }
        // Some fields required ADMIN rights
        if (!Permission::check('ADMIN')) {
            $fields->removeByName('FailedLoginCount');
        }
        // Some things should never be shown outside of SecurityAdmin
        if (get_class($ctrl) != SecurityAdmin::class && !Permission::check('ADMIN', 'any', $this->owner)) {
            $fields->removeByName([
                'DirectGroups',
                'Permissions',
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
        if (!$this->owner->isLockedOut()) {
            return _t('BaseMemberExtension.MEMBER_NOT_LOCKED', 'Member is not locked');
        }

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
     * Force member login
     * (since Member::login has been deprecated but is really useful)
     *
     * @param HTTPRequest $request
     * @param boolean $remember
     * @return void
     */
    public function forceLogin($request = null, $remember = false)
    {
        if ($request === null) {
            $request = Controller::curr()->getRequest();
        }
        Security::setCurrentUser($this->owner);
        $identityStore = Injector::inst()->get(IdentityStore::class);
        return $identityStore->logIn($this->owner, $remember, $request);
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
     * @return Member[]|ArrayList
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
