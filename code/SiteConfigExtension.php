<?php
namespace LeKoala\Base;

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
 * @property \SilverStripe\SiteConfig\SiteConfig|\LeKoala\Base\SiteConfigExtension $owner
 * @property string $ContactInfos
 * @property string $ContactAddress
 * @property string $ContactEmail
 * @property string $ContactPhone
 * @property string $DefaultFromEmail
 * @property string $EmailFooter
 * @property string $FooterText
 * @property string $Copyright
 * @property string $GoogleAnalyticsCode
 * @property string $GoogleMapsApiKey
 */
class SiteConfigExtension extends DataExtension
{
    private static $db = [
        // Contact Details
        "ContactInfos" => "Text",
        "ContactAddress" => "Varchar(199)",
        "ContactEmail" => "Varchar(199)",
        "ContactPhone" => "Varchar",
        "LegalName" => "Varchar(199)",
        // Emails
        "DefaultFromEmail" => "Varchar(199)",
        "EmailFooter" => "Text",
        // Footer
        "FooterText" => "HTMLText",
        "Copyright" => "HTMLText",
        // External Services
        "GoogleAnalyticsCode" => "Varchar(59)",
        "GoogleMapsApiKey" => "Varchar(59)",
        // Site config
        "ForceSSL" => "Boolean",
    ];
    public function updateCMSFields(FieldList $fields)
    {
        // Contact fields
        $ContactsHeader = new HeaderField('ContactsHeader', 'Contacts');
        $fields->addFieldToTab('Root.Main', $ContactsHeader);
        $ContactInfos = new TextareaField('ContactInfos');
        $fields->addFieldToTab('Root.Main', $ContactInfos);
        $ContactEmail = new TextField('ContactEmail');
        $fields->addFieldToTab('Root.Main', $ContactEmail);
        $ContactPhone = new TextField('ContactPhone');
        $fields->addFieldToTab('Root.Main', $ContactPhone);
        $ContactAddress = new TextField('ContactAddress');
        $fields->addFieldToTab('Root.Main', $ContactAddress);
        $LegalName = new TextField('LegalName');
        $fields->addFieldToTab('Root.Main', $LegalName);
        // Emails
        $EmailsHeader = new HeaderField('EmailsHeader', 'Email');
        $fields->addFieldToTab('Root.Main', $EmailsHeader);
        $DefaultFromEmail = new TextField('DefaultFromEmail');
        $fields->addFieldToTab('Root.Main', $DefaultFromEmail);
        $EmailFooter = new TextareaField('EmailFooter');
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
