<?php

namespace LeKoala\Base\Security;

use SilverStripe\Forms\Tab;
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
use LeKoala\Base\Security\MemberAudit;
use SilverStripe\Security\LoginAttempt;
use LeKoala\Base\Forms\CmsInlineFormAction;
use LeKoala\Base\Forms\GridField\GridFieldHelper;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;

/**
 * Class \LeKoala\Base\Security\BaseSecurityAdminExtension
 *
 * @property \SilverStripe\Admin\SecurityAdmin|\LeKoala\Base\Security\BaseSecurityAdminExtension $owner
 */
class BaseSecurityAdminExtension extends Extension
{
    private static $allowed_actions = [
        'doClearLogs',
        'doRotateLogs',
    ];

    /**
     * @return SecurityAdmin
     */
    protected function getSecurityAdmin()
    {
        return $this->owner;
    }

    protected function redirectWithStatus($msg, $code = 200)
    {
        $admin = $this->getSecurityAdmin();
        $response = $admin->getResponse();
        $response->setStatusCode($code);
        $response->addHeader('X-Status', rawurlencode($msg));
        return $admin->redirectBack();
    }

    public function doClearLogs(HTTPRequest $request)
    {
        foreach ($this->getLogFiles() as $logFile) {
            unlink($logFile);
        }
        $msg = "Logs cleared";
        return $this->redirectWithStatus($msg);
    }

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

    public function updateEditForm(Form $form)
    {
        // Roles are confusing
        $form->Fields()->removeByName('Roles');

        // In security, we only show group members + current item (to avoid issue when creating stuff)
        $request = $this->getRequest();
        $dirParts = explode('/', $request->remaining());
        $currentID = isset($dirParts[3]) ? [$dirParts[3]] : [];
        $membersOfGroups = BaseMemberExtension::getMembersFromSecurityGroups($currentID);
        $members = $this->getMembersGridField($form);
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

        // Show groups
        $cols = GridFieldHelper::getGridFieldDataColumns($members->getConfig());
        $cols->setDisplayFields(array(
            'FirstName' => 'FirstName',
            'Surname' => 'Surname',
            'Email' => 'Email',
            'DirectGroupsList' => 'Direct Groups',
        ));

        if (Security::config()->login_recording) {
            $this->addAuditTab($form);
        }

        if (Permission::check('ADMIN')) {
            $this->addMemberAuditTab($form);
            $this->addLogTab($form);
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
     * @return array
     */
    protected function getLogFiles()
    {
        $logDir = Director::baseFolder();
        $logFiles = glob($logDir . '/*.log');
        return $logFiles;
    }

    protected function addLogTab(Form $form)
    {
        $logFiles = $this->getLogFiles();
        $logTab = new Tab('Logs', _t('BaseSecurityAdminExtension.Logs', 'Logs'));
        $form->Fields()->addFieldsToTab('Root', $logTab);

        foreach ($logFiles as $logFile) {
            $logName = pathinfo($logFile, PATHINFO_FILENAME);

            $logTab->push(new HeaderField($logName, ucwords($logName)));

            $filemtime = filemtime($logFile);
            $filesize = filesize($logFile);

            $logTab->push(new AlertField($logName . 'Alert', _t('BaseSecurityAdminExtension.LogAlert', "Last updated on {updated}", [
                'updated' => date('Y-m-d H:i:s', $filemtime),
            ])));

            $lastLines = '<pre>' . FileHelper::tail($logFile, 10) . '</pre>';

            $logTab->push(new LiteralField($logName, $lastLines));
            $logTab->push(new LiteralField($logName . 'Size', '<p>' . _t('BaseSecurityAdminExtension.LogSize', "Total size is {size}", [
                'size' => FileHelper::humanFilesize($filesize)
            ]) . '</p>'));
        }

        $clearLogsBtn = new CmsInlineFormAction('doClearLogs', _t('BaseSecurityAdminExtension.doClearLogs', 'Clear Logs'));
        $logTab->push($clearLogsBtn);
        $rotateLogsBtn = new CmsInlineFormAction('doRotateLogs', _t('BaseSecurityAdminExtension.doRotateLogs', 'Rotate Logs'));
        $logTab->push($rotateLogsBtn);
    }

    protected function addAuditTab(Form $form)
    {
        $fields = $form->Fields();
        $auditTab = new Tab('SecurityAudit', _t('BaseSecurityAdminExtension.SecurityAudit', "Security Audit"));
        $fields->addFieldsToTab('Root', $auditTab);

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

    protected function addMemberAuditTab(Form $form)
    {
        $fields = $form->Fields();
        $MemberAudit_SNG = MemberAudit::singleton();
        $list = MemberAudit::get();
        if ($list->count()) {
            $auditTab = new Tab('MemberAuditTab', _t('BaseSecurityAdminExtension.MemberAuditTab', "Members Audit"));
            $fields->addFieldsToTab('Root', $auditTab);

            $MemberAuditGrid = new GridField('MemberAudit', _t('BaseSecurityAdminExtension.MemberAudit', "Members audit events"), $list, GridFieldConfig_RecordViewer::create());
            $MemberAuditGrid->setForm($form);
            $GridFieldDataColumns = GridFieldHelper::getGridFieldDataColumns($MemberAuditGrid->getConfig());
            $GridFieldDataColumns->setDisplayFields([
                'Created' => $MemberAudit_SNG->fieldLabel('Created'),
                'Member.Title' => $MemberAudit_SNG->fieldLabel('Member'),
                'Event' => $MemberAudit_SNG->fieldLabel('Event'),
                'AuditDataShort' => $MemberAudit_SNG->fieldLabel('AuditData'),
            ]);
            $auditTab->push($MemberAuditGrid);
        }
    }


    /**
     * @param Form $form
     * @return GridField
     */
    protected function getMembersGridField(Form $form)
    {
        return $form->Fields()->dataFieldByName('Members');
    }

    /**
     * @param Form $form
     * @return GridField
     */
    protected function getGroupsGridField(Form $form)
    {
        return $form->Fields()->dataFieldByName('Groups');
    }
}
