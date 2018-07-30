<?php

namespace LeKoala\Base\Privacy;

use LeKoala\Base\Forms\BaseForm;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\RequestHandler;
use LeKoala\Base\Forms\BuildableFieldList;
use LeKoala\Base\Privacy\PrivacyNoticePage;
use LeKoala\Base\Privacy\TermsAndConditionsPage;
use SilverStripe\Security\Member;
use SilverStripe\View\Requirements;

class PrivacyForm extends BaseForm
{
    /**
     * @var Member
     */
    protected $record;
    protected $recordType = Member::class;

    protected function requirements()
    {
        Requirements::css('base/css/privacy.css');
    }
    protected function buildFields(BuildableFieldList $fields)
    {
        $record = $this->record;

        $startLabel = _t('PrivacyForm.IAGREE', "I read and I agree with the");

        $PrivacyNoticePage = DataObject::get_one(PrivacyNoticePage::class);
        $TermsAndConditionsPage = DataObject::get_one(TermsAndConditionsPage::class);

        $header = _t('PrivacyForm.PLEASEREVIEW', "Please review the following agreements to continue");
        $fields->addHeader($header);

        if ($PrivacyNoticePage && $PrivacyNoticePage->Content) {
            $CheckPrivacyTitle = $startLabel . ' <a href="'.$PrivacyNoticePage->Link().'" target="_blank">'.$PrivacyNoticePage->Title.'</a>';
            $PrivacyContent = '<div class="PrivacyForm-Box">' . $PrivacyNoticePage->Content . '</div>';
            $fields->addLiteral($PrivacyContent);
            $fields->addCheckbox("CheckPrivacy", $CheckPrivacyTitle, ["required" => "required"]);
        }
        if ($TermsAndConditionsPage->Content) {
            $CheckTermsTitle = $startLabel . ' <a href="'.$TermsAndConditionsPage->Link().'" target="_blank">'.$TermsAndConditionsPage->Title.'</a>';
            $TermsContent = '<div class="PrivacyForm-Box">' . $TermsAndConditionsPage->Content . '</div>';

            $fields->addLiteral($TermsContent);
            $fields->addCheckbox("CheckTerms", $CheckTermsTitle, ["required" => "required"]);
        }
        return $fields;
    }

    public function doSubmit($data)
    {
        if (isset($data['CheckPrivacy'])) {
            $this->record->PrivacyChecked = date('Y-m-d H:i:s');
        }
        if (isset($data['CheckTerms'])) {
            $this->record->TermsChecked = date('Y-m-d H:i:s');
        }
        $this->record->write();

        return $this->getController()->redirectBack();
    }
}
