<?php
namespace LeKoala\Base\Contact;

use SilverStripe\Control\Email\Email;
use LeKoala\Base\Contact\ContactSubmission;
use SilverStripe\Control\HTTPRequest;

/**
 * Class \LeKoala\Base\Contact\ContactPageController
 *
 * @property \LeKoala\Base\Contact\ContactPage dataRecord
 * @method \LeKoala\Base\Contact\ContactPage data()
 * @mixin \LeKoala\Base\Contact\ContactPage dataRecord
 */
class ContactPageController extends \PageController
{
    private static $allowed_actions = [
        "index",
        "doSend",
    ];
    public function index(HTTPRequest $request)
    {
        // $this->sendDummyEmail();
        $this->SiteConfig()->requireGoogleMaps();
        // Use non namespaced name
        return $this->renderWith(['ContactPage', 'Page']);
    }
    protected function sendDummyEmail()
    {
        l('sending dummy email');
        $address = Email::config()->admin_email;
        $emailInst = new Email();
        $emailInst->setTo($address);
        $emailInst->setSubject("Dummy email");
        $emailInst->setBody("Dummy body, <strong>with html!</strong>");
        $emailInst->send();
    }
    public function doSend()
    {
        $request = $this->getRequest();
        // Collect data
        $name = $request->postVar('name');
        $subject = $request->postVar('subject');
        $phone = $request->postVar('phone');
        $email = $request->postVar('email');
        $message = $request->postVar('message');
        // Validate data
        if (trim($name) == '') {
            $this->sessionMessage(_t("ContactPageController.ERR_ENTER_NAME", "Entrez votre nom"), "bad");
            return $this->redirectBack();
        } elseif (trim($email) == '') {
            $this->sessionMessage(_t("ContactPageController.ERR_ENTER_EMAIL", "Entrez votre emali"), "bad");
            return $this->redirectBack();
        } elseif (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $this->sessionMessage(_t("ContactPageController.ERR_ENTER_VALIDEMAIL", "Entrez un email valide"), "bad");
            return $this->redirectBack();
        } elseif (trim($message) == '') {
            $this->sessionMessage(_t("ContactPageController.ERR_ENTER_MESSAGE", "Entrez votre message"), "bad");
            return $this->redirectBack();
        }
        // Register submission
        $submission = new ContactSubmission();
        $submission->PageID = $this->data()->ID;
        $submission->Name = $name;
        $submission->Subject = $subject;
        $submission->Message = $message;
        $submission->Email = $email;
        $submission->Phone = $phone;
        $submission->write();
        // Send by email
        $address = $this->data()->Email;
        $result = $submission->sendByEmail($address);
        if ($result) {
            $this->sessionMessage(_t("ContactPageController.MESSAGE_SENT", "Votre message a bien été envoyé"), "good");
        } else {
            $this->sessionMessage(_t("ContactPageController.MESSAGE_ERROR", "Votre message n'a pas été envoyé"), "bad");
            $this->getLogger()->info("Failed recipients: " . implode(',', $emailInst->getFailedRecipients()));
        }
        return $this->redirectBack();
    }
}
