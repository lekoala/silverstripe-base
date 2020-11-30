<?php

namespace LeKoala\Base\Contact;

use LeKoala\Base\Forms\BaseForm;
use LeKoala\Base\Forms\BuildableFieldList;
use SilverStripe\Forms\RequiredFields;
use LeKoala\Base\Forms\GoogleRecaptchaField;

/**
 * A contact form
 *
 * Easily inject your own
 *
 * SilverStripe\Core\Injector\Injector:
 *   LeKoala\Base\Contact\ContactForm:
 *     class: MyContactForm
 */
class ContactForm extends BaseForm
{
    protected $jsValidationEnabled = true;

    protected function buildFields(BuildableFieldList $fields)
    {
        $fields->setPlaceholderAsLabel(true);
        $fields->group(function (BuildableFieldList $fields) {
            $fields->addText('Name', _t('ContactForm.Name', 'Name'));
            $fields->addText('Company', _t('ContactForm.Company', 'Company'));
        });
        $fields->group(function (BuildableFieldList $fields) {
            $fields->addEmail('Email', _t('ContactForm.Email3', 'Email'));
            $fields->addText('Phone', _t('ContactForm.Phone', 'Phone'));
        });

        $fields->addText('Subject', _t('ContactForm.Subject', 'Subject'));
        $fields->addTextarea('Message', _t('ContactForm.Message', 'Message'));

        if (GoogleRecaptchaField::isSetupReady()) {
            $fields->push(new GoogleRecaptchaField);
        }

        return $fields;
    }

    protected function buildActions(BuildableFieldList $actions)
    {
        // This cannot be doSend since we have it on the controller and may cause confusion onSubmit
        $doSend = $actions->addAction("doSubmit", _t('ContactForm.SEND', 'Send your message'));
        $doSend->addExtraClass('d-block w-100');
        return $actions;
    }

    protected function buildValidator(BuildableFieldList $fields)
    {
        $validator = new RequiredFields;
        $validator->addRequiredField('Name');
        $validator->addRequiredField('Email');
        $validator->addRequiredField('Subject');
        $validator->addRequiredField('Message');
        return $validator;
    }

    public function doSubmit($data)
    {
        if (GoogleRecaptchaField::isSetupReady()) {
            GoogleRecaptchaField::validateResponse($data);
        }

        $controller = $this->getController();
        // Register submission
        $submission = new ContactSubmission();
        $this->saveInto($submission);
        $submission->PageID = $controller->data()->ID;
        $submission->write();
        // Send by email
        $address = $controller->data()->Email;
        $result = $submission->sendByEmail($address);

        $error = false;
        $state = 'good';
        if ($result) {
            $msg = _t("ContactPageController.MESSAGE_SENT", "Votre message a bien été envoyé");
        } else {
            $msg = _t("ContactPageController.MESSAGE_ERROR", "Votre message n'a pas été envoyé");
            $error = true;
            $state = 'bad';
        }

        if ($controller->hasMethod('returnMessage')) {
            return $controller->returnMessage($msg, $error);
        }

        // Fallback if we use the contact form on another controller
        $this->sessionMessage($msg, $state);
        return $this->getController()->redirectBack();
    }
}
