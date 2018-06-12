<?php

namespace LeKoala\Base\Privacy;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;

class PrivacyMemberExtension extends DataExtension
{
    private static $db = [
        "PrivacyChecked" => "DBDatetime",
        "TermsChecked" => "DBDatetime"
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
