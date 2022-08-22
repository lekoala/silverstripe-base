<?php

namespace LeKoala\Base\Security;

use Exception;
use LeKoala\Base\Controllers\HasSession;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Security\Permission;
use LeKoala\Base\Security\MemberAudit;
use LeKoala\CommonExtensions\ValidationStatusExtension;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\ORM\ValidationException;

/**
 * A lot of base functionalities for your members
 *
 * Most group of functions are grouped within traits when possible
 *
 * @link https://docs.silverstripe.org/en/4/developer_guides/extending/how_tos/track_member_logins/
 * @property \SilverStripe\Security\Member|\LeKoala\Base\Security\BaseMemberExtension|\LeKoala\Base\Security\TwoFactorMemberExtension $owner
 * @property string $LastVisited
 * @property int $NumVisit
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Security\MemberAudit[] Audits()
 */
class BaseMemberExtension extends DataExtension
{
    use MasqueradeMember;
    use MemberAuthenticatorExtensions;
    use HasSession;

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

    public function validate(ValidationResult $validationResult)
    {
        if ($this->owner->Email && !filter_var($this->owner->Email, FILTER_VALIDATE_EMAIL)) {
            $validationResult->addError("Email is not valid");
        }
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
        // We can avoid bots by allowing to tick a box
        // if ($this->owner->isLockedOut() && GoogleRecaptchaField::isSetupReady()) {
        //     $result->addError('There was too many failed login attempt. Please check the captcha box to try again.');
        //     return;
        // }

        // Admin can always log in
        if (Permission::check('ADMIN', 'any', $this->owner)) {
            return;
        }
    }

    public function onValidationDisable()
    {
        $this->owner->audit('Validation Status Changed', ['Status' => ValidationStatusExtension::VALIDATION_STATUS_DISABLED]);
    }

    public function onValidationApprove()
    {
        $this->owner->audit('Validation Status Changed', ['Status' => ValidationStatusExtension::VALIDATION_STATUS_APPROVED]);
    }

    public function onBeforeWrite()
    {
        if ($this->owner->Email) {
            $this->owner->Email = trim($this->owner->Email);
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

    /**
     * @param string $password
     * @param ValidationResult $valid
     * @return void
     */
    public function onBeforeChangePassword($password, &$valid)
    {
        if (!$password && $this->owner->isChanged("Password")) {
            throw new ValidationException("Your password cannot be empty");
        }
    }

    /**
     * @param string $password
     * @param ValidationResult $valid
     * @return void
     */
    public function onAfterChangePassword($password, $valid)
    {
        if ($valid->isValid()) {
            $this->owner->audit('password_changed_success');

            // Can prove useful to send custom toast message or notification
            self::getSession()->set('PasswordChanged', 1);
        } else {
            $this->owner->audit('password_changed_error');
        }
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
            // check config flag
            $login_as_only_default_admin = $this->owner->config()->login_as_only_default_admin;
            $canLoginAs = false;
            if ($login_as_only_default_admin) {
                try {
                    $defaultAdmin = DefaultAdminService::getDefaultAdminUsername();
                    if ($defaultAdmin == Security::getCurrentUser()->Email) {
                        $canLoginAs = true;
                    }
                } catch (Exception $ex) {
                }
            } else {
                $canLoginAs = true;
            }
            if ($canLoginAs) {
                $actions->push($doLoginAs = new CustomAction('doLoginAs', 'Login as'));
            }
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
