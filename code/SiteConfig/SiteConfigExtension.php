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

/**
 * Class \LeKoala\Base\SiteConfigExtension
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\LeKoala\Base\SiteConfig\SiteConfigExtension $owner
 * @property string $ContactEmail
 * @property string $ContactPhone
 * @property string $ContactAddress
 * @property string $ContactInfos
 * @property string $LegalName
 * @property string $DefaultFromEmail
 * @property string $EmailFooter
 * @property string $FooterText
 * @property string $Copyright
 * @property string $GoogleAnalyticsCode
 * @property string $GoogleMapsApiKey
 * @property boolean $ForceSSL
 */
class SiteConfigExtension extends DataExtension
{
    private static $db = [
        // Contact Details
        "ContactEmail" => "Varchar(199)",
        "ContactPhone" => "Varchar",
        "ContactAddress" => "Varchar(199)", // A geocodable address
        "ContactInfos" => "HTMLText", // Additionnal infos with map, links etc
        "LegalName" => "Varchar(199)", // The legal name of the company, for copyright use
        // Emails
        "DefaultFromEmail" => "Varchar(199)",
        "EmailFooter" => "Text",
        // Footer
        "FooterText" => "HTMLText",
        "Copyright" => "HTMLText", // A custom copyright text, otherwise defaults to (year) - Legal Name
        // External Services
        "GoogleAnalyticsCode" => "Varchar(59)",
        "GoogleMapsApiKey" => "Varchar(59)",
        // Site config
        "ForceSSL" => "Boolean",
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
        $LegalName = new TextField('LegalName', _t('Global.LegalName', 'Legal Name'));
        $fields->addFieldToTab('Root.Main', $LegalName);
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
        // External Services
        $externalServicesTab = new Tab("ExternalServices");
        $fields->addFieldToTab('Root', $externalServicesTab);
        $GoogleAnalyticsCode = new TextField('GoogleAnalyticsCode');
        $externalServicesTab->push($GoogleAnalyticsCode);
        $GoogleMapsApiKey = new TextField('GoogleMapsApiKey');
        $externalServicesTab->push($GoogleMapsApiKey);
        // Config
        $fields->addFieldsToTab('Root.Access', new CheckboxField('ForceSSL'));
    }
    public function ContactAddressMapLink()
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($this->owner->ContactAddress);
    }
    public function requireGoogleMaps()
    {
        if (!$this->owner->GoogleMapsApiKey) {
            return false;
        }
        Requirements::javascript('https://maps.googleapis.com/maps/api/js?key=' . $this->owner->GoogleMapsApiKey);
        return true;
    }
    public function requireGoogleAnalytics()
    {
        if (!Director::isLive()) {
            return false;
        }
        if (!$this->owner->GoogleAnalyticsCode) {
            return false;
        }
        $script = <<<JS
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
ga('create', {$this->owner->GoogleAnalyticsCode}, 'auto');
ga('send', 'pageview');
JS;
        Requirements::customScript($script, "GoogleAnalytics");
        return true;
    }
}
