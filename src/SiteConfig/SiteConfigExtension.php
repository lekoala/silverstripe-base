<?php
namespace LeKoala\Base\SiteConfig;

use SilverStripe\Forms\Tab;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\TextareaField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\HeaderField;
use LeKoala\Base\Forms\Builder;
use SilverStripe\Forms\CheckboxField;
use LeKoala\Base\View\CookieConsent;

/**
 * Class \LeKoala\Base\SiteConfigExtension
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\LeKoala\Base\SiteConfig\SiteConfigExtension $owner
 * @property string $ContactEmail
 * @property string $ContactPhone
 * @property string $ContactAddress
 * @property string $ContactInfos
 * @property string $LegalName
 * @property string $CompanyRegistrationNumber
 * @property string $LegalCourt
 * @property string $DefaultFromEmail
 * @property string $EmailFooter
 * @property string $FooterText
 * @property string $Copyright
 */
class SiteConfigExtension extends DataExtension
{
    const EXTERNAL_SERVICES_TAB = 'ExternalServices';

    private static $db = [
        // Contact Details
        "ContactEmail" => "Varchar(199)",
        "ContactPhone" => "Varchar",
        "ContactAddress" => "Varchar(199)", // A geocodable address
        "ContactInfos" => "HTMLText", // Additionnal infos with map, links etc
        "LegalName" => "Varchar(199)", // The legal name of the company, for copyright use
        "CompanyRegistrationNumber" => "Varchar(59)", // The legal number
        "LegalCourt" => "Varchar(59)", // The legal court
        // Emails
        "DefaultFromEmail" => "Varchar(199)",
        "EmailFooter" => "Text",
        // Footer
        "FooterText" => "HTMLText",
        "Copyright" => "HTMLText", // A custom copyright text, otherwise defaults to (year) - Legal Name
    ];

    private static $translate = [
        "FooterText", "Copyright", "EmailFooter", "ContactInfos"
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Contact fields
        $ContactsHeader = new HeaderField('ContactsHeader', _t('Global.ContactsSettings', 'Contacts settings'));
        $fields->addFieldToTab('Root.Main', $ContactsHeader);
        $ContactEmail = new TextField('ContactEmail', _t('Global.Email', 'Email'));
        $fields->addFieldToTab('Root.Main', $ContactEmail);
        $ContactPhone = new TextField('ContactPhone', _t('Global.Phone', 'Phone'));
        $fields->addFieldToTab('Root.Main', $ContactPhone);
        $ContactAddress = new TextField('ContactAddress', _t('Global.Address', 'Address'));
        $fields->addFieldToTab('Root.Main', $ContactAddress);
        $ContactInfos = new HTMLEditorField('ContactInfos', _t('Global.ContactInfos', 'Contact details'));
        // See https://docs.silverstripe.org/en/4/developer_guides/forms/field_types/htmleditorfield/
        $ContactInfos->setRows(7);
        $fields->addFieldToTab('Root.Main', $ContactInfos);
        // Privacy and legal stuff
        $LegalName = new TextField('LegalName', _t('Global.LegalName', 'Legal Name'));
        $fields->addFieldToTab('Root.Legal', $LegalName);
        $CompanyRegistrationNumber = new TextField('CompanyRegistrationNumber', _t('Global.CompanyRegistrationNumber', 'Company Registration Number'));
        $fields->addFieldToTab('Root.Legal', $CompanyRegistrationNumber);
        $LegalCourt = new TextField('LegalCourt', _t('Global.LegalCourt', 'Legal Court'));
        $fields->addFieldToTab('Root.Legal', $LegalCourt);
        // Emails
        $EmailsHeader = new HeaderField('EmailsHeader', _t('Global.EmailSettings', 'Email settings'));
        $fields->addFieldToTab('Root.Main', $EmailsHeader);
        $DefaultFromEmail = new TextField('DefaultFromEmail', _t('Global.DefaultFromEmail', 'Default From Email'));
        $fields->addFieldToTab('Root.Main', $DefaultFromEmail);
        $EmailFooter = new TextareaField('EmailFooter', _t('Global.EmailFooter', 'Email Footer'));
        $fields->addFieldToTab('Root.Main', $EmailFooter);
        // Footer
        $FooterText = new HTMLEditorField('FooterText');
        $FooterText->setRows(5);
        $fields->addFieldToTab('Root.Footer', $FooterText);
        $Copyright = new HTMLEditorField('Copyright');
        $Copyright->setRows(2);
        $fields->addFieldToTab('Root.Footer', $Copyright);
    }

    /**
     * A map link
     *
     * @return string
     */
    public function ContactAddressMapLink()
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($this->owner->ContactAddress);
    }

    /**
     * @return string
     */
    public function CopyrightYear()
    {
        return date('Y');
    }
}
