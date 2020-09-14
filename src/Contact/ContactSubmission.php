<?php

namespace LeKoala\Base\Contact;

use LeKoala\Base\Actions\CustomAction;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Contact\ContactPage;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Controllers\HasLogger;
use LeKoala\Base\ORM\FieldType\DBJson;
use SilverStripe\Core\Convert;

/**
 * Class \LeKoala\Base\Contact\ContactSubmission
 *
 * @property string $Name
 * @property string $Subject
 * @property string $Message
 * @property string $Email
 * @property string $Phone
 * @property string $Company
 * @property string $ExtraData
 * @property string $EmailResults
 * @property boolean $EmailSent
 * @property int $PageID
 * @method \LeKoala\Base\Contact\ContactPage Page()
 */
class ContactSubmission extends DataObject
{
    use HasLogger;

    private static $table_name = 'ContactSubmission'; // When using namespace, specify table name
    private static $db = [
        "Name" => "Varchar(191)",
        "Subject" => "Varchar(191)",
        "Message" => "Text",
        "Email" => "Varchar",
        "Phone" => "Varchar",
        "Company" => "Varchar",
        "ExtraData" => DBJson::class,
        "EmailResults" => "Text",
        "EmailSent" => "Boolean",
    ];
    private static $has_one = [
        "Page" => ContactPage::class
    ];
    private static $default_sort = 'Created DESC';
    private static $summary_fields = [
        "Name", "Email", "Created"
    ];

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->Email) {
            $this->Email = filter_var($this->Email, FILTER_SANITIZE_EMAIL);
        }
        if ($this->Message) {
            $this->Message = Convert::raw2xml(strip_tags($this->Message));
        }
        if ($this->Name) {
            $this->Name = Convert::raw2xml(strip_tags($this->Name));
        }
        if ($this->Phone) {
            $this->Phone = Convert::raw2xml(strip_tags($this->Phone));
        }
        if ($this->Company) {
            $this->Company = Convert::raw2xml(strip_tags($this->Company));
        }
    }

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        if (!$this->EmailSent) {
            $send = new CustomAction("doSend", "Send");
            $actions->push($send);
        }

        return $actions;
    }

    public function doSend()
    {
        $res = $this->sendByEmail();

        if ($res) {
            return 'Email sent';
        }
        return 'Failed to send';
    }

    /**
     * @param string $address
     * @return bool
     */
    public function sendByEmail($address = null)
    {
        $SiteConfig = SiteConfig::current_site_config();
        if ($SiteConfig->hasMethod("UseFormSpree") && $SiteConfig->UseFormSpree()) {
            $this->EmailResults = "Sent with FormSpree";
            return $this->write();
        }
        if (!$address) {
            $address = $SiteConfig->ContactEmail;
        }
        if (!$address) {
            $address = Email::config()->admin_email;
        }
        $name = $this->Name;
        $subject = $this->Subject;
        $phone = $this->Phone;
        $message = $this->Message;
        $email = $this->Email;

        $e_subject = 'You\'ve been contacted by ' . $name . ' [' . $SiteConfig->Title . ']';
        if ($subject) {
            $e_subject = $subject . ' [' . $SiteConfig->Title . ']';
        }

        $e_body = "You have been contacted by: $name<br/>";
        $e_body .= "E-mail: $email<br/>";
        if ($phone) {
            $e_body .= "Phone: $phone<br/>";
        }
        $e_content = "<br/><hr/>Message:<br/>$message<hr/>";
        $msg = $e_body . $e_content;
        $ex = null;
        try {
            $emailInst = Email::create();
            $emailInst->setTo($address);
            $emailInst->setSubject($e_subject);
            // $emailInst->setBody($msg);
            $emailInst->addData(['EmailContent' => $msg]);
            $emailInst->setReplyTo($email, $name);

            $result = $emailInst->send();

            $this->EmailSent = true;
        } catch (\Exception $e) {
            $result = $e->getMessage();
            $this->EmailSent = false;
            $ex = $e;
            self::getLogger()->info($e);
            return false;
        }
        $this->EmailResults = is_string($result) ? $result : json_encode($result);
        return $this->write();
    }
}
