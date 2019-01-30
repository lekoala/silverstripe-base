<?php
namespace LeKoala\Base\Contact;

use SilverStripe\ORM\DataObject;
use LeKoala\Base\Contact\ContactPage;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Controllers\HasLogger;

/**
 * Class \LeKoala\Base\Contact\ContactSubmission
 *
 * @property string $Name
 * @property string $Subject
 * @property string $Message
 * @property string $Email
 * @property string $Phone
 * @property string $Company
 * @property string $EmailResults
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
        "EmailResults" => "Text",
    ];
    private static $has_one = [
        "Page" => ContactPage::class
    ];
    private static $default_sort = 'Created DESC';
    private static $summary_fields = [
        "Name", "Email", "Created"
    ];

    /**
     * @param string $address
     * @return bool
     */
    public function sendByEmail($address = null)
    {
        $SiteConfig = SiteConfig::current_site_config();
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
        $msg = wordwrap($e_body . $e_content, 70);
        $ex = null;
        try {
            $emailInst = new Email();
            $emailInst->setTo($address);
            $emailInst->setSubject($e_subject);
            $emailInst->setBody($msg);
            $emailInst->setReplyTo($email);
            $result = $emailInst->send();
        } catch (\Exception $e) {
            $result = $e->getMessage();
            $ex = $e;
            self::getLogger()->info($e);
            return false;
        }
        $this->EmailResults = is_string($result) ? $result : json_encode($result);
        return $this->write();
    }
}
