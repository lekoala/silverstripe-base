<?php
namespace LeKoala\Base\Controllers;

use LeKoala\Base\News\NewsPage;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Contact\ContactPage;
use LeKoala\Base\Faq\FaqPage;
use LeKoala\Base\Privacy\PrivacyNoticePage;
use LeKoala\Base\Privacy\CookiesRequiredPage;
use LeKoala\Base\Privacy\TermsAndConditionsPage;

trait PageGetters
{
    /**
     * @return TermsAndConditionsPage
     */
    public function TermsAndConditionsPage()
    {
        return DataObject::get_one(TermsAndConditionsPage::class);
    }

    /**
     * @return PrivacyNoticePage
     */
    public function PrivacyNoticePage()
    {
        return DataObject::get_one(PrivacyNoticePage::class);
    }

    /**
     * @return CookiesRequiredPage
     */
    public function CookiesRequiredPage()
    {
        return DataObject::get_one(CookiesRequiredPage::class);
    }

    /**
     * @return ContactPage
     */
    public function ContactPage()
    {
        return DataObject::get_one(ContactPage::class);
    }

    /**
     * @return NewsPage
     */
    public function NewsPage()
    {
        return DataObject::get_one(NewsPage::class);
    }

    /**
     * @return FaqPage
     */
    public function FaqPage()
    {
        return DataObject::get_one(FaqPage::class);
    }
}
