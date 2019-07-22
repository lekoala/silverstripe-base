<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Actions\CustomAction;

/**
 * Allow to enable/disable login for your objects based on status
 * See BaseMemberExtension for usage
 */
class ValidationStatusExtension extends DataExtension
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
            self::VALIDATION_STATUS_PENDING => _t('ValidationStatusExtension.VALIDATION_STATUS_PENDING', 'pending'),
            self::VALIDATION_STATUS_APPROVED => _t('ValidationStatusExtension.VALIDATION_STATUS_APPROVED', 'approved'),
            self::VALIDATION_STATUS_DISABLED => _t('ValidationStatusExtension.VALIDATION_STATUS_DISABLED', 'disabled'),
        ];
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->makeFieldReadonly('ValidationStatus');
    }

    public function updateCMSActions(FieldList $actions)
    {
        if ($this->IsValidationStatusPending()) {
            $actions->push(new CustomAction('doValidationApprove', _t('ValidationStatusExtension.APPROVE', 'Approve')));
            $actions->push(new CustomAction('doValidationDisable', _t('ValidationStatusExtension.DISABLE', 'Disable')));
        }
        if ($this->IsValidationStatusApproved()) {
            $actions->push(new CustomAction('doValidationDisable', _t('ValidationStatusExtension.DISABLE', 'Disable')));
        }
        if ($this->IsValidationStatusDisabled()) {
            $actions->push(new CustomAction('doValidationApprove', _t('ValidationStatusExtension.APPROVE', 'Approve')));
        }
    }

    public function doValidationApprove()
    {
        $this->owner->ValidationStatus = self::VALIDATION_STATUS_APPROVED;
        $this->owner->write();

        $auditID = $this->owner->audit('Validation Status Changed', ['Status' => self::VALIDATION_STATUS_APPROVED]);

        return _t('ValidationStatusExtension.APPROVED', 'Approved');
    }

    public function doValidationDisable()
    {
        $this->owner->ValidationStatus = self::VALIDATION_STATUS_DISABLED;
        $this->owner->write();

        $auditID =  $this->owner->audit('Validation Status Changed', ['Status' => self::VALIDATION_STATUS_DISABLED]);

        return _t('ValidationStatusExtension.DISABLED', 'Disabled');
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
