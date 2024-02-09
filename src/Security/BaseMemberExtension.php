<?php

namespace LeKoala\Base\Security;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use LeKoala\Base\Helpers\IPHelper;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use LeKoala\Base\Helpers\FormHelper;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Control\Email\Email;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\LoginAttempt;
use LeKoala\Base\Controllers\HasSession;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\MemberPassword;
use SilverStripe\Security\PasswordEncryptor;
use SilverStripe\Security\DefaultAdminService;
use LeKoala\CommonExtensions\ValidationStatusExtension;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * A lot of base functionalities for your members
 *
 * Most group of functions are grouped within traits when possible
 *
 * @property \SilverStripe\Security\Member|\LeKoala\Base\Security\BaseMemberExtension $owner
 */
class BaseMemberExtension extends DataExtension
{
    use MasqueradeMember;
    use HasSession;

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
     * @return array<string>
     */
    public function getUsedIps()
    {
        return LoginAttempt::get()->filter([
            'MemberID' => $this->owner->ID,
            'Status' => 'Success',
        ])
            ->where('Created < DATE_SUB(NOW(), INTERVAL 10 SECOND)')
            ->columnUnique('IP');
    }

    /**
     * Check if this is one of the recent passwords of the user
     *
     * @param string $password
     * @param int $count how many previous passwords to check (defaults to 1)
     * @return ?MemberPassword
     */
    public function isRecentPassword($password, $count = 1)
    {
        $max = $count + 1;

        /** @var MemberPassword[] $previousPasswords */
        $previousPasswords = MemberPassword::get()
            ->where(['"MemberPassword"."MemberID"' => $this->owner->ID])
            ->sort('"Created" DESC, "ID" DESC')
            ->limit($max);

        $i = 0;
        foreach ($previousPasswords as $previousPassword) {
            // The first password is the current one, ignore
            $i++;
            if ($i == 1) {
                // ignore
            } else {
                if ($previousPassword->checkPassword($password)) {
                    return $previousPassword;
                }
            }
        }
        return null;
    }

    /**
     * @param string $password
     * @return ValidationResult
     */
    public function checkPassword($password)
    {
        /** @var Member $owner */
        $owner = $this->owner;
        $validationResult = new ValidationResult();

        $result = null;
        if (!$password) {
            // Empty password
            $result = false;
        } elseif (!$owner->PasswordEncryption) {
            // Plain password
            $result = $password == $owner->Password;
        }

        if ($result === null) {
            $encryptor = PasswordEncryptor::create_for_algorithm($owner->PasswordEncryption);
            $result = $encryptor->check($owner->Password ?? '', $password, $owner->Salt, $owner);
        }

        // Convert bool result to validationResult
        if ($result === false) {
            $validationResult->addError('Invalid password');
        }
        return $validationResult;
    }

    /**
     * @return bool|string
     */
    public function getPasswordResetLink()
    {
        $token = $this->owner->generateAutologinTokenAndStoreHash();
        $resetLink = Security::getPasswordResetLink($this->owner, $token);
        return Director::absoluteURL($resetLink);
    }

    /**
     * @param ValidationResult $validationResult
     * @return void
     */
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
     * To prevent login, add errors to validation result
     *
     * This means canLogIn is called before 2FA, for instance
     *
     * @param ValidationResult $result
     * @return void
     */
    public function canLogIn(ValidationResult $result)
    {
        /** @var Member|BaseMemberExtension|TwoFactorMemberExtension $owner */
        $owner = $this->owner;

        // Login without an actual password in the db is not allowed anymore
        if ($owner && !$owner->Password && $owner->Email == Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME')) {
            $owner->Password = Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD');
            $owner->write();
        }

        // Ip whitelist for users with cms access (empty by default)
        // SilverStripe\Security\Security:
        //   admin_ip_whitelist:
        //     - 127.0.0.1/255
        $adminIps = Security::config()->admin_ip_whitelist;

        $request = Controller::curr()->getRequest();
        $requestIp = $request->getIP();

        // If we whitelist by IP, check we are using a valid IP
        if (!empty($adminIps)) {
            // Are we a cms user ?
            $isCmsUser = Permission::check('CMS_Access', 'any', $owner);

            // Are we from a trusted ip ?
            $trusted = IPHelper::checkIp($requestIp, $adminIps);

            // Even when coming from invalid ips, if we have 2fa, we can trust the user
            if (!$trusted && TwoFactorMemberExtension::isEnabled()) {
                $need2Fa = $owner->NeedTwoFactorAuth();
                $has2Fa = count($owner->AvailableTwoFactorMethod()) > 0 ? true : false;
                $trusted = !$need2Fa || $has2Fa;
            }

            // Cms user is not trusted => cannot login
            if ($isCmsUser && !$trusted) {
                // No 2fa method to validate important account on invalid ips
                $this->owner->audit('invalid_ip_admin', ['ip' => $requestIp]);
                $result->addError(_t('BaseMemberExtension.ADMIN_IP_INVALID', "Your ip address {address} is not whitelisted for this account level", ['address' => $requestIp]));
            }
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

    /**
     * @return void
     */
    public function onBeforeWrite()
    {
        if ($this->owner->Email) {
            $this->owner->Email = trim($this->owner->Email);
        }
    }

    public function afterMemberLoggedIn()
    {
        // Notify user if needed
        if (Member::config()->notify_new_ip) {
            $result = $this->checkIfNewIp();

            if ($result) {
                $request = Controller::curr()->getRequest();
                $requestIp = $request->getIP();
                $requestUa = $request->getHeader('User-Agent') ?? "undefined";

                /** @var Email $email */
                $email = Email::create()
                    ->setHTMLTemplate('NotifyNewIPEmail')
                    ->setData($this->owner)
                    ->setSubject(_t(
                        'NotifyNewIPEmail.SUBJECT',
                        "Your account has been accessed from a new IP Address",
                        'Email subject'
                    ))
                    ->addData('RequestIP', $requestIp)
                    ->addData('RequestUA', $requestUa)
                    ->addData('AccessTime', date('Y-m-d H:i:s') . ' (' . date_default_timezone_get() . ')')
                    ->setTo($this->owner->Email);

                $email->send();
            }
        }
    }

    /**
     * Called by CookieAuthenticationHandler
     */
    public function memberAutoLoggedIn()
    {
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
    }

    public function updateMemberPasswordField($password)
    {
        // This is actually annoying, and we don't allow blank passwords anyway
        // @link https://github.com/silverstripe/silverstripe-framework/issues/10940
        $password->setCanBeEmpty(true);
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

            // email sending is controlled by Member::config()->notify_password_change

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

        FormHelper::makeFieldReadonly($fields, [
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
        $currentUser = Security::getCurrentUser();
        if (!$currentUser) {
            return;
        }

        $isAdmin = Permission::check('ADMIN');

        // Admin can unlock people
        if ($isAdmin && $this->owner->isLockedOut()) {
            $actions->push($doUnlock = new CustomAction('doUnlock', 'Unlock'));
        }

        // Login as (but cannot login as yourself :-) )
        if ($isAdmin && $this->owner->ID != $currentUser->ID) {
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
        $user = Security::getCurrentUser();
        return $user && $this->owner->ID !== $user->ID;
    }

    /**
     * @return boolean
     */
    public function IsAdmin()
    {
        return Permission::check('CMS_ACCESS', 'any', $this->owner);
    }

    /**
     * @return boolean
     */
    public function IsSuperAdmin()
    {
        return Permission::check('ADMIN', 'any', $this->owner);
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

    public function checkIfNewIp()
    {
        $ips = $this->owner->getUsedIps();
        if (!empty($ips)) {
            $request = Controller::curr()->getRequest();
            $requestIp = $request->getIP();
            if (!in_array($requestIp, $ips)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array An array of IDs
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
