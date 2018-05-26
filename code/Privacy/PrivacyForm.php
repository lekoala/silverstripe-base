<?php

namespace LeKoala\Base\Privacy;

use LeKoala\Base\Forms\BaseForm;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\RequestHandler;
use LeKoala\Base\Forms\BuildableFieldList;
use LeKoala\Base\Privacy\PrivacyNoticePage;
use LeKoala\Base\Privacy\TermsAndConditionsPage;

class PrivacyForm extends BaseForm
{
    protected function buildFields(BuildableFieldList $fields)
    {
        $record = $this->record;
        $startLabel = _t('PrivacyForm.IAGREE', "I read and I agree with the");
        $PrivacyNoticePage = DataObject::get_one(PrivacyNoticePage::class);
        $TermsAndConditionsPage = DataObject::get_one(TermsAndConditionsPage::class);
        $CheckPrivacyTitle = $startLabel . ' <a href="'.$PrivacyNoticePage->Link().'" target="_blank">'.$PrivacyNoticePage->Title.'</a>';
        $CheckTermsTitle = $startLabel . ' <a href="'.$TermsAndConditionsPage->Link().'" target="_blank">'.$TermsAndConditionsPage->Title.'</a>';

        $PrivacyContent = '<div class="privacy-box">' . $PrivacyNoticePage->Content . '</div>';
        $TermsContent = '<div class="privacy-box">' . $TermsAndConditionsPage->Content . '</div>';

        $fields->addLiteral($PrivacyContent);
        $fields->addCheckbox("CheckPrivacy", $CheckPrivacyTitle, ["required" => "required", "html" => true]);
        $fields->addLiteral($TermsContent);
        $fields->addCheckbox("CheckTerms", $CheckTermsTitle, ["required" => "required", "html" => true]);
        return $fields;
    }

    protected function buildActions(BuildableFieldList $actions)
    {
        $actions->addAction("doSubmit");
        return $actions;
    }
}
