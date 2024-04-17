<?php

namespace LeKoala\Base\Security;

use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use LeKoala\Base\Forms\AlertField;
use SilverStripe\Control\Director;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Security\Security;
use LeKoala\Base\Helpers\FileHelper;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Permission;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\LoginAttempt;
use LeKoala\CmsActions\CmsInlineFormAction;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\Base\Forms\GridField\GridFieldHelper;
use SilverStripe\Forms\GridField\GridFieldConfig;
use LeKoala\Base\ORM\Search\WildcardSearchContext;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;

/**
 * BaseSecurityAdminExtension
 *
 * @property \SilverStripe\Admin\SecurityAdmin|\LeKoala\Base\Security\BaseSecurityAdminExtension $owner
 */
class BaseSecurityAdminExtension extends Extension
{
    /**
     * @var array<string>
     */
    private static $allowed_actions = [
        'doClearLogs',
        'doRotateLogs',
        // new tabs
        'security_audit',
        'logs',
    ];

    /**
     * @return SecurityAdmin
     */
    protected function getSecurityAdmin()
    {
        return $this->owner;
    }

    /**
     * @return void
     */
    public function init()
    {
        $owner = $this->getSecurityAdmin();
        // Kill roles
        $models = $owner::config()->get('managed_models');
        unset($models['roles']);

        // Add extra tabs
        if (Security::config()->login_recording) {
            $models['security_audit'] = [
                'title' => 'Security Audit',
                'dataClass' => LoginAttempt::class,
            ];
        }

        if (Permission::check('ADMIN')) {
            $models['logs'] = [
                'title' => 'Logs',
                'dataClass' => LoginAttempt::class, // pseudo dataClass
            ];
        }

        $owner::config()->set('managed_models', $models);
    }

    /**
     * @param GridFieldConfig $config
     * @return void
     */
    public function updateGridFieldConfig(GridFieldConfig $config)
    {
        $url = explode("/", $this->owner->getRequest()->getURL());
        $segment = $url[2] ?? "";
        if (in_array($segment, ['security_audit', 'members_audit', 'logs'])) {
            $config->removeComponentsByType(GridFieldImportButton::class);
        }
    }

    /**
     * @param string $msg
     * @param integer $code
     * @return HTTPResponse
     */
    protected function redirectWithStatus($msg, $code = 200)
    {
        $admin = $this->getSecurityAdmin();
        $response = $admin->getResponse();
        $response->setStatusCode($code);
        $response->addHeader('X-Status', rawurlencode($msg));
        return $admin->redirectBack();
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function doClearLogs(HTTPRequest $request)
    {
        foreach ($this->getLogFiles() as $logFile) {
            unlink($logFile);
        }
        $msg = "Logs cleared";
        return $this->redirectWithStatus($msg);
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function doRotateLogs(HTTPRequest $request)
    {
        foreach ($this->getLogFiles() as $logFile) {
            if (strpos($logFile, '-') !== false) {
                continue;
            }
            $newname = dirname($logFile) . '/' . pathinfo($logFile, PATHINFO_FILENAME) . '-' . date('Ymd') . '.log';
            rename($logFile, $newname);
        }
        $msg = "Logs rotated";
        return $this->redirectWithStatus($msg);
    }

    /**
     * @param Form $form
     * @return void
     */
    public function updateEditForm(Form $form)
    {
        // In security, we only show group members + current item (to avoid issue when creating stuff)
        $request = $this->getRequest();
        $dirParts = explode('/', $request->remaining());
        $currentID = isset($dirParts[3]) ? [$dirParts[3]] : [];
        $members = $this->getMembersGridField($form);
        if ($members) {
            $membersOfGroups = BaseMemberExtension::getMembersFromSecurityGroups($currentID);
            $members->setList($membersOfGroups);

            // Add message
            $MembersOnlyGroups = AlertField::create(
                'MembersOnlyGroups',
                _t(
                    'BaseSecurityAdminExtension.MembersOnlyGroups',
                    'Only group members are shown. To add a user to a group, link it from an existing group.'
                ),
                'info'
            );
            $form->Fields()->insertAfter('Members', $MembersOnlyGroups);

            // Show groups / 2FA
            $cols = GridFieldHelper::getGridFieldDataColumns($members->getConfig());
            $displayFields = $cols->getDisplayFields($members);
            $displayFields['DirectGroupsList'] = 'Direct Groups';
            if (TwoFactorMemberExtension::isEnabled()) {
                $displayFields['Is2FaConfigured'] = '2FA';
            }
            $cols->setDisplayFields($displayFields);

            // Better search
            $filter = GridFieldHelper::getGridFieldFilterHeader($members->getConfig());
            $wildCardHeader = WildcardSearchContext::fromContext($filter->getSearchContext($members));
            // $wildCardHeader->setWildcardFilters(['FirstName', 'Surname', 'Email']);
            $wildCardHeader->replaceInFilterHeader($filter);
        }

        $url = explode("/", $this->owner->getRequest()->getURL());
        $segment = $url[2] ?? "";
        if ($segment == "security_audit") {
            if (Security::config()->login_recording) {
                $this->addAuditTab($form);
            }
        }
        if ($segment == "logs") {
            if (Permission::check('ADMIN')) {
                $this->addLogTab($form);
            }
        }
    }

    /**
     * @return HTTPRequest
     */
    protected function getRequest()
    {
        return $this->owner->getRequest();
    }

    /**
     * @return array<string>
     */
    protected function getLogFiles()
    {
        $logDir = Director::baseFolder();
        $logFiles = glob($logDir . '/*.log');
        if ($logFiles === false) {
            $logFiles = [];
        }
        return $logFiles;
    }

    /**
     * @param Form $form
     * @return void
     */
    protected function addLogTab(Form $form)
    {
        $logFiles = $this->getLogFiles();
        $logTab = $form->Fields();
        $logTab->removeByName('logs');

        foreach ($logFiles as $logFile) {
            $logName = pathinfo($logFile, PATHINFO_FILENAME);

            $logTab->push(new HeaderField($logName, ucwords($logName)));

            $filemtime = filemtime($logFile);
            if ($filemtime) {
                $logTab->push(new AlertField($logName . 'Alert', _t('BaseSecurityAdminExtension.LogAlert', "Last updated on {updated}", [
                    'updated' => date('Y-m-d H:i:s', $filemtime),
                ])));
            }

            $lastLines = '<pre>' . FileHelper::tail($logFile, 10) . '</pre>';
            $logTab->push(new LiteralField($logName, $lastLines));

            $filesize = filesize($logFile);
            if ($filesize) {
                $logTab->push(new LiteralField($logName . 'Size', '<p>' . _t('BaseSecurityAdminExtension.LogSize', "Total size is {size}", [
                    'size' => FileHelper::humanFilesize($filesize)
                ]) . '</p>'));
            }
        }

        $clearLogsBtn = new CmsInlineFormAction('doClearLogs', _t('BaseSecurityAdminExtension.doClearLogs', 'Clear Logs'));
        $logTab->push($clearLogsBtn);
        $rotateLogsBtn = new CmsInlineFormAction('doRotateLogs', _t('BaseSecurityAdminExtension.doRotateLogs', 'Rotate Logs'));
        $logTab->push($rotateLogsBtn);
    }

    /**
     * @param Form $form
     * @return void
     */
    protected function addAuditTab(Form $form)
    {
        $auditTab = $form->Fields();
        $auditTab->removeByName('security_audit');

        $Member_SNG = Member::singleton();
        $membersLocked = Member::get()->where('LockedOutUntil > NOW()');
        if ($membersLocked->count()) {
            $membersLockedGrid = new GridField('MembersLocked', _t('BaseSecurityAdminExtension.LockedMembers', "Locked Members"), $membersLocked, GridFieldConfig_RecordViewer::create());
            $membersLockedGrid->setForm($form);
            $GridFieldDataColumns = GridFieldHelper::getGridFieldDataColumns($membersLockedGrid->getConfig());
            $GridFieldDataColumns->setDisplayFields([
                'Title' => $Member_SNG->fieldLabel('Title'),
                'Email' => $Member_SNG->fieldLabel('Email'),
                'LockedOutUntil' => $Member_SNG->fieldLabel('LockedOutUntil'),
                'FailedLoginCount' => $Member_SNG->fieldLabel('FailedLoginCount'),
            ]);
            $auditTab->push($membersLockedGrid);
        }

        $LoginAttempt_SNG = LoginAttempt::singleton();

        $getMembersFromSecurityGroupsIDs = BaseMemberExtension::getMembersFromSecurityGroupsIDs();
        $recentAdminLogins = LoginAttempt::get()->filter([
            'Status' => 'Success',
            'MemberID' => $getMembersFromSecurityGroupsIDs
        ])->limit(10)->sort('Created DESC');
        $recentAdminLoginsGridConfig = GridFieldConfig_RecordViewer::create();
        $GridFieldDataColumns = GridFieldHelper::getGridFieldDataColumns($recentAdminLoginsGridConfig);
        $GridFieldDataColumns->setDisplayFields([
            'Created' => $LoginAttempt_SNG->fieldLabel('Created'),
            'IP' => $LoginAttempt_SNG->fieldLabel('IP'),
            'Member.Title' => $Member_SNG->fieldLabel('Title'),
            'Member.Email' => $Member_SNG->fieldLabel('Email'),
        ]);
        $recentAdminLoginsGrid = new GridField('RecentAdminLogins', _t('BaseSecurityAdminExtension.RecentAdminLogins', "Recent Admin Logins"), $recentAdminLogins, $recentAdminLoginsGridConfig);
        $recentAdminLoginsGrid->setForm($form);
        $auditTab->push($recentAdminLoginsGrid);

        $recentPasswordFailures = LoginAttempt::get()->filter('Status', 'Failure')->limit(10)->sort('Created DESC');
        $recentPasswordFailuresGridConfig = GridFieldConfig_RecordViewer::create();
        $GridFieldDataColumns = GridFieldHelper::getGridFieldDataColumns($recentPasswordFailuresGridConfig);
        $GridFieldDataColumns->setDisplayFields([
            'Created' => $LoginAttempt_SNG->fieldLabel('Created'),
            'IP' => $LoginAttempt_SNG->fieldLabel('IP'),
            'Member.Title' => $Member_SNG->fieldLabel('Title'),
            'Member.Email' => $Member_SNG->fieldLabel('Email'),
            'Member.FailedLoginCount' => $Member_SNG->fieldLabel('FailedLoginCount'),
        ]);
        $recentPasswordFailuresGrid = new GridField('RecentPasswordFailures', _t('BaseSecurityAdminExtension.RecentPasswordFailures', "Recent Password Failures"), $recentPasswordFailures, $recentPasswordFailuresGridConfig);
        $recentPasswordFailuresGrid->setForm($form);
        $auditTab->push($recentPasswordFailuresGrid);
    }

    /**
     * @param Form $form
     * @return ?GridField
     */
    protected function getMembersGridField(Form $form)
    {
        $field = $form->Fields()->dataFieldByName('Members');
        if (!$field) {
            $field = $form->Fields()->dataFieldByName('users');
        }
        //@phpstan-ignore-next-line
        return $field;
    }

    /**
     * @param Form $form
     * @return ?GridField
     */
    protected function getGroupsGridField(Form $form)
    {
        $field = $form->Fields()->dataFieldByName('Groups');
        if (!$field) {
            $field = $form->Fields()->dataFieldByName('groups');
        }
        //@phpstan-ignore-next-line
        return $field;
    }
}
