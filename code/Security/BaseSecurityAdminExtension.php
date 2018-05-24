<?php
namespace LeKoala\Base\Security;

use SilverStripe\Forms\Tab;
use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;

/**
 */
class BaseSecurityAdminExtension extends Extension
{
    public function updateEditForm(Form $form)
    {
        // Roles are confusing
        $form->Fields()->removeByName('Roles');

        // In security, we only show group members
        $membersOfGroups = BaseMemberExtension::getMembersFromSecurityGroups();
        $members = $this->getMembersGridField($form);
        $members->setList($membersOfGroups);

        // Show groups
        $cols = $members->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $cols->setDisplayFields(array(
            'FirstName' => 'FirstName',
            'Surname' => 'Surname',
            'Email' => 'Email',
            'DirectGroupsList' => 'Direct Groups',
        ));

        if (Security::config()->login_recording) {
            $this->addAuditTab($form);
        }
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
            $GridFieldDataColumns = $membersLockedGrid->getConfig()->getComponentByType(GridFieldDataColumns::class);
            $GridFieldDataColumns->setDisplayFields([
                'Title' => $Member_SNG->fieldLabel('Title'),
                'Email' => $Member_SNG->fieldLabel('Email'),
                'LockedOutUntil' => $Member_SNG->fieldLabel('LockedOutUntil'),
                'FailedLoginCount' => $Member_SNG->fieldLabel('FailedLoginCount'),
                ]);
            $auditTab->push($membersLockedGrid);
        }

        $recentPasswordFailures = LoginAttempt::get()->filter('Status', 'Failure')->limit(10)->sort('Created DESC');
        $recentPasswordFailuresGridConfig = GridFieldConfig_RecordViewer::create();
        /* @var $GridFieldDataColumns GridFieldDataColumns */
        $LoginAttempt_SNG = LoginAttempt::singleton();
        $GridFieldDataColumns = $recentPasswordFailuresGridConfig->getComponentByType(GridFieldDataColumns::class);
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
