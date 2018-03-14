<?php
namespace LeKoala\Base\Contact;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Contact\ContactSubmission;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;
/**
 * Class \LeKoala\Base\Contact\ContactPage
 *
 * @property string $Address
 * @property string $Phone
 * @property string $Email
 * @property float $Latitude
 * @property float $Longitude
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Contact\ContactSubmission[] Submissions()
 */
class ContactPage extends \Page
{
    private static $table_name = 'ContactPage'; // When using namespace, specify table name
    private static $db = [
        "Address" => "Varchar(191)",
        "Phone" => "Varchar(51)",
        "Email" => "Varchar(191)",
        //
        "Latitude" => "Float(10,6)",
        "Longitude" => "Float(10,6)",
    ];
    private static $has_many = [
        "Submissions" => ContactSubmission::class
    ];
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab('Root.Details', new TextField('Address'));
        $fields->addFieldsToTab('Root.Details', new TextField('Phone'));
        $fields->addFieldsToTab('Root.Details', new TextField('Email'));
        $fields->addFieldsToTab('Root.Map', new TextField('Latitude'));
        $fields->addFieldsToTab('Root.Map', new TextField('Longitude'));
        $fields->addFieldsToTab('Root.Map', new LiteralField('LatLonHelper', 'You can use a website like <a href="https://www.latlong.net/" target="_blank">LatLong.net</a> to find your coordinates'));
        $SubmissionsConfig = GridFieldConfig_RecordEditor::create();
        $Submissions = new GridField('Submissions',$this->fieldLabel('Submission'), $this->Submissions(), $SubmissionsConfig);
        $fields->addFieldsToTab('Root.Submissions', $Submissions);
        return $fields;
    }
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $SiteConfig = SiteConfig::current_site_config();
        if (!$this->Address) {
            $this->Address = $SiteConfig->ContactAddress;
        }
        if (!$this->Phone) {
            $this->Phone = $SiteConfig->ContactPhone;
        }
        if (!$this->Email) {
            $this->Email = $SiteConfig->ContactEmail;
        }
    }
    public function GoogleMapsLink()
    {
        return 'https://maps.google.com/?q=' . urlencode($this->Address);
    }
}