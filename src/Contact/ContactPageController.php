<?php
namespace LeKoala\Base\Contact;

use SilverStripe\Control\Email\Email;
use LeKoala\Base\Contact\ContactSubmission;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Director;

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
        return $this;
    }

    /**
     * @return ContactForm
     */
    public function ContactForm()
    {
        $form = ContactForm::create($this);
        return $form;
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

    /**
     * This handler is for plain html forms (eg if using a template instead of a Form object)
     * @return HTTPResponse
     */
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
            return $this->returnMessage(_t("ContactPageController.ERR_ENTER_NAME", "Entrez votre nom"), true);
        } elseif (trim($email) == '') {
            return $this->returnMessage(_t("ContactPageController.ERR_ENTER_EMAIL", "Entrez votre email"), true);
        } elseif (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->returnMessage(_t("ContactPageController.ERR_ENTER_VALIDEMAIL", "Entrez un email valide"), true);
        } elseif (trim($message) == '') {
            return $this->returnMessage(_t("ContactPageController.ERR_ENTER_MESSAGE", "Entrez votre message"), true);
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
        if (!$result) {
            return $this->returnMessage(_t("ContactPageController.MESSAGE_ERROR", "Votre message n'a pas été envoyé"), true);
        }
        return $this->returnMessage(_t("ContactPageController.MESSAGE_SENT", "Votre message a bien été envoyé"));
    }

    /**
     * @param string $msg
     * @param boolean $error
     * @return HTTPResponse
     */
    public function returnMessage($msg, $error = false)
    {
        $status = $error ? 'good' : 'bad';
        if (Director::is_ajax()) {
            if ($error) {
                return $this->httpError(400, $msg);
            }
            return $msg;
        } else {
            $this->sessionMessage($msg, $status);
            return $this->redirectBack();
        }
    }
}
