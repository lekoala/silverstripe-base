<?php

namespace LeKoala\Base\Contact;

use Exception;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use LeKoala\Base\Contact\ContactSubmission;
use LeKoala\Base\Forms\GoogleRecaptchaField;
use SilverStripe\Core\Convert;
use SilverStripe\Security\SecurityToken;

/**
 * Class \LeKoala\Base\Contact\ContactPageController
 *
 * @property \LeKoala\Base\Contact\ContactPage dataRecord
 * @method \LeKoala\Base\Contact\ContactPage data()
 * @mixin \LeKoala\Base\Contact\ContactPage dataRecord
 */
class ContactPageController extends \PageController
{
    /**
     * @config
     * @var boolean
     */
    private static $use_distinct_succes_page = true;

    private static $allowed_actions = [
        "index",
        'messageSent',
        "doSend",
        'ContactForm',
    ];


    public function init()
    {
        parent::init();

        /*
        LeKoala\Base\Contact\ContactPageController:
          theme_files: true
        */
        if ($this->config()->theme_files) {
            Requirements::themedCSS('contact.css');
            Requirements::themedJavascript('contact.js');
        }
    }

    public function index(HTTPRequest $request)
    {
        // $this->sendDummyEmail();
        $this->SiteConfig()->requireGoogleMaps();
        return $this;
    }

    public function messageSent(HTTPRequest $request)
    {
        $error = $request->getVar('error');
        if ($error) {
            $Content = _t("ContactPageController.MESSAGE_ERROR", "Votre message n'a pas été envoyé");
        } else {
            $Content = _t("ContactPageController.MESSAGE_SENT", "Votre message a bien été envoyé");
        }

        return $this->render([
            'Content' => $Content
        ]);
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

    public function GoogleRecaptchaField()
    {
        if (GoogleRecaptchaField::isSetupReady()) {
            return new GoogleRecaptchaField;
        }
        return false;
    }

    /**
     * This handler is for plain html forms (eg if using a template instead of a Form object)
     * @return HTTPResponse
     */
    public function doSend()
    {
        $request = $this->getRequest();

        // SecurityID
        if (!SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }

        $data = $request->postVars();

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

        // Recaptcha
        if (GoogleRecaptchaField::isSetupReady()) {
            try {
                GoogleRecaptchaField::validateResponse($data);
            } catch (Exception $ex) {
                return $this->returnMessage($ex->getMessage(), false);
            }
        }

        // Collect extra data
        $ignore = ['name', 'subject', 'phone', 'email', 'message', 'SecurityID', 'g-recaptcha-response'];
        $postVars = $request->postVars();
        $extraData = [];
        foreach ($postVars as $postVarKey => $postVarValue) {
            if (in_array($postVarKey, $ignore)) {
                continue;
            }
            $extraData[$postVarKey] = Convert::raw2xml($postVarValue);
        }

        // Register submission - see onBeforeWrite for sanitization
        $submission = new ContactSubmission();
        $submission->PageID = $this->data()->ID;
        $submission->Name = $name;
        $submission->Subject = $subject;
        $submission->Message = $message;
        $submission->Email = $email;
        $submission->Phone = $phone;
        $submission->ExtraData = $extraData;
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
     * Used in template if no success content is provided
     * @return string
     */
    public function DefaultSuccessContent()
    {
        return _t("ContactPageController.MESSAGE_SENT", "Votre message a bien été envoyé");
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
        }
        if (self::config()->use_distinct_succes_page) {
            $link = $this->Link('messageSent');
            // in case of error, redirect back
            if ($error) {
                $this->sessionMessage($msg, $status);
                return $this->redirectBack();
            }
            return $this->redirect($link);
        } else {
            $this->sessionMessage($msg, $status);
            return $this->redirectBack();
        }
    }
}
