<?php

namespace LeKoala\Base\Privacy;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;

/**
 * Class \LeKoala\Base\Privacy\PrivacyMemberExtension
 *
 * @property \SilverStripe\Security\Member|\LeKoala\Base\Privacy\PrivacyMemberExtension $owner
 * @property string $PrivacyChecked
 * @property string $TermsChecked
 */
class PrivacyMemberExtension extends DataExtension
{
    private static $db = [
        "PrivacyChecked" => "DBDatetime",
        "TermsChecked" => "DBDatetime"
    ];
    private static $removed_fields = [
        "PrivacyChecked", "TermsChecked"
    ];

    public function needToCheckPrivacyOrTerms()
    {
        return $this->needsToCheckPrivacy() || $this->needsToCheckTerms();
    }

    public function needsToCheckPrivacy()
    {
        $p = DataObject::get_one(PrivacyNoticePage::class);
        if (!$p || !$p->Content) {
            return false;
        }
        return $this->owner->PrivacyChecked ? false: true;
    }

    public function needsToCheckTerms()
    {
        $p = DataObject::get_one(TermsAndConditionsPage::class);
        if (!$p || !$p->Content) {
            return false;
        }
        return $this->owner->TermsChecked ? false: true;
    }
}
