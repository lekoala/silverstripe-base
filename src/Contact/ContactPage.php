<?php

namespace LeKoala\Base\Contact;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Contact\ContactSubmission;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

/**
 * Class \LeKoala\Base\Contact\ContactPage
 *
 * @property string $Address
 * @property string $Infos
 * @property boolean $ShowInfosOnly
 * @property string $Phone
 * @property string $Email
 * @property float $Latitude
 * @property float $Longitude
 * @property string $MapEmbed
 * @property string $SuccessContent
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Contact\ContactSubmission[] Submissions()
 */
class ContactPage extends \Page
{
    private static $table_name = 'ContactPage'; // When using namespace, specify table name
    private static $db = [
        "Address" => "Varchar(199)", // A geocodable address
        "Infos" => "HTMLText", // Additionnal infos with map, links etc
        "ShowInfosOnly" => "Boolean", // Instead of address if you have custom stuff
        "Phone" => "Varchar(51)",
        "Email" => "Varchar(199)",
        // TODO: refactor into GeoExtension
        "Latitude" => "Float(10,6)",
        "Longitude" => "Float(10,6)",
        "MapEmbed" => "HTMLText",
        "SuccessContent" => "HTMLText",
    ];
    private static $has_many = [
        "Submissions" => ContactSubmission::class
    ];
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab('Root.Details', new TextField('Address'));
        $fields->addFieldsToTab('Root.Details', $Infos = new HTMLEditorField('Infos'));
        $Infos->setRows(10);
        $Infos->addExtraClass('stacked');
        $fields->addFieldsToTab('Root.Details', new CheckboxField('ShowInfosOnly'));
        $fields->addFieldsToTab('Root.Details', new TextField('Phone'));
        $fields->addFieldsToTab('Root.Details', new TextField('Email'));
        //
        $fields->addFieldsToTab('Root.Map', new TextField('Latitude'));
        $fields->addFieldsToTab('Root.Map', new TextField('Longitude'));
        $fields->addFieldsToTab('Root.Map', new LiteralField(
            'LatLonHelper',
            'You can use a website like <a href="https://www.latlong.net/" target="_blank">LatLong.net</a> to find your coordinates<br/><br/>'
        ));
        $fields->addFieldsToTab('Root.Map', new TextareaField("MapEmbed"));
        $fields->addFieldsToTab('Root.Map', new LiteralField(
            'MapEmbedHelper',
            'You can use a website like <a href="https://www.google.be/maps" target="_blank">Google Map</a> to create a map<br/><br/>'
        ));

        $fields->addFieldsToTab('Root.Success', $SuccessContent = new HTMLEditorField('SuccessContent'));
        $SuccessContent->setRows(10);
        $SuccessContent->addExtraClass('stacked');

        $SiteConfig = SiteConfig::current_site_config();
        if ($SiteConfig->hasMethod("UseFormSpree") && $SiteConfig->UseFormSpree()) {
            // Hide submissions
        } else {
            $SubmissionsConfig = GridFieldConfig_RecordEditor::create();
            $Submissions = new GridField('Submissions', $this->fieldLabel('Submission'), $this->Submissions(), $SubmissionsConfig);
            $fields->addFieldsToTab('Root.Submissions', $Submissions);
        }

        return $fields;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $SiteConfig = SiteConfig::current_site_config();
        if (!$this->Address) {
            $this->Address = $SiteConfig->ContactAddress;
        }
        if (!$this->Infos) {
            $this->Infos = $SiteConfig->ContactInfos;
        }
        if (!$this->Phone) {
            $this->Phone = $SiteConfig->ContactPhone;
        }
        if (!$this->Email) {
            $this->Email = $SiteConfig->ContactEmail;
        }
        // Ensure responsiveness
        if ($this->MapEmbed && strpos($this->MapEmbed, "max-width") === false) {
            $this->MapEmbed = str_replace('style="', 'style="max-width:100%;', $this->MapEmbed);
        }
    }
    public function GoogleMapsLink()
    {
        return 'https://maps.google.com/?q=' . urlencode($this->Address);
    }
}
