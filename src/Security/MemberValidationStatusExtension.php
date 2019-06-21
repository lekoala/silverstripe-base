<?php
namespace LeKoala\Base\Security;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Actions\CustomAction;

/**
 * Allow to enable/disable login for your members based on status
 * See BaseMemberExtension for usage
 */
class MemberValidationStatusExtension extends DataExtension
{
    const VALIDATION_STATUS_PENDING = 'pending';
    const VALIDATION_STATUS_APPROVED = 'approved';
    const VALIDATION_STATUS_DISABLED = 'disabled';

    private static $db = [
        "ValidationStatus" => "NiceEnum('pending,approved,disabled')"
    ];

    /**
     * @return array
     */
    public static function listStatus()
    {
        return [
            self::VALIDATION_STATUS_PENDING => _t('MemberValidationStatusExtension.VALIDATION_STATUS_PENDING', 'pending'),
            self::VALIDATION_STATUS_APPROVED => _t('MemberValidationStatusExtension.VALIDATION_STATUS_APPROVED', 'approved'),
            self::VALIDATION_STATUS_DISABLED => _t('MemberValidationStatusExtension.VALIDATION_STATUS_DISABLED', 'disabled'),
        ];
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->makeFieldReadonly('ValidationStatus');
    }

    public function updateCMSActions(FieldList $actions)
    {
        if ($this->IsValidationStatusPending()) {
            $actions->push(new CustomAction('doValidationApprove', _t('MemberValidationStatusExtension.APPROVE', 'Approve')));
            $actions->push(new CustomAction('doValidationDisable', _t('MemberValidationStatusExtension.DISABLE', 'Disable')));
        }
        if ($this->IsValidationStatusApproved()) {
            $actions->push(new CustomAction('doValidationDisable', _t('MemberValidationStatusExtension.DISABLE', 'Disable')));
        }
        if ($this->IsValidationStatusDisabled()) {
            $actions->push(new CustomAction('doValidationApprove', _t('MemberValidationStatusExtension.APPROVE', 'Approve')));
        }
    }

    public function doValidationApprove()
    {
        $this->owner->ValidationStatus = self::VALIDATION_STATUS_APPROVED;
        $this->owner->write();

        $this->owner->audit('Validation Status Changed', ['Status' => self::VALIDATION_STATUS_APPROVED]);
    }

    public function doValidationDisable()
    {
        $this->owner->ValidationStatus = self::VALIDATION_STATUS_DISABLED;
        $this->owner->write();

        $this->owner->audit('Validation Status Changed', ['Status' => self::VALIDATION_STATUS_DISABLED]);
    }

    public function IsValidationStatusPending()
    {
        return $this->owner->ValidationStatus == self::VALIDATION_STATUS_PENDING;
    }

    public function IsValidationStatusApproved()
    {
        return $this->owner->ValidationStatus == self::VALIDATION_STATUS_APPROVED;
    }

    public function IsValidationStatusDisabled()
    {
        return $this->owner->ValidationStatus == self::VALIDATION_STATUS_DISABLED;
    }
}
