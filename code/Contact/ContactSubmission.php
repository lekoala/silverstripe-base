<?php
namespace LeKoala\Base\Contact;

use SilverStripe\ORM\DataObject;
use LeKoala\Base\Contact\ContactPage;
use SilverStripe\Control\Email\Email;

/**
 * @property string $Name
 * @property string $Subject
 * @property string $Message
 * @property string $Email
 * @property string $Phone
 * @property string $EmailResults
 */
class ContactSubmission extends DataObject
{
    private static $table_name = 'ContactSubmission'; // When using namespace, specify table name

    private static $db = [
        "Name" => "Varchar(191)",
        "Subject" => "Varchar(191)",
        "Message" => "Text",
        "Email" => "Varchar",
        "Phone" => "Varchar",
        "EmailResults" => "Text",
    ];
    private static $has_one = [
        "Page" => ContactPage::class
    ];

    private static $default_sort = 'Created DESC';

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


        $e_subject = 'You\'ve been contacted by ' . $name . '.';
        if ($subject) {
            $e_subject = $subject . ' [' . $SiteConfig->Title . ']';
        }

        $e_body = "You have been contacted by: $name" . PHP_EOL . PHP_EOL;
        $e_reply = "E-mail: $email\r\nPhone: $phone";
        $e_content = "Message:\r\n$message" . PHP_EOL . PHP_EOL;

        $msg = wordwrap($e_body . $e_content . $e_reply, 70);

        $ex = null;
        try {
            $emailInst = new Email();
            $emailInst->setTo($address);
            $emailInst->setSubject($e_subject);
            $emailInst->setBody($msg);
            $emailInst->setReplyTo($email);

            $result = $emailInst->send();
        } catch (\Exception $e) {
            $result = null;
            $ex = $e;
            $this->getLogger()->info($e);
        }

        $this->EmailResults = json_encode($result);
        $this->write();
    }
}
