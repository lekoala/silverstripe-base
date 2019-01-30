<?php
namespace LeKoala\Base\Contact;

use LeKoala\Base\Forms\BaseForm;
use LeKoala\Base\Forms\BuildableFieldList;
use SilverStripe\Forms\RequiredFields;

class ContactForm extends BaseForm
{
    protected $jsValidationEnabled = true;

    protected function buildFields(BuildableFieldList $fields)
    {
        $fields->setPlaceholderAsLabel(true);
        $fields->group(function (BuildableFieldList $fields) {
            $fields->addText('Name');
            $fields->addText('Company');
        });
        $fields->group(function (BuildableFieldList $fields) {
            $fields->addEmail('Email');
            $fields->addText('Phone');
        });
        $fields->addText('Subject');
        $fields->addTextarea('Message');

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

    public function doSubmit()
    {
        $controller = $this->getController();
        // Register submission
        $submission = new ContactSubmission();
        $this->saveInto($submission);
        $submission->PageID = $controller->data()->ID;
        $submission->write();
        // Send by email
        $address = $controller->data()->Email;
        $result = $submission->sendByEmail($address);
        if ($result) {
            $this->sessionMessage(_t("ContactPageController.MESSAGE_SENT", "Votre message a bien été envoyé"), "good");
        } else {
            $this->sessionMessage(_t("ContactPageController.MESSAGE_ERROR", "Votre message n'a pas été envoyé"), "bad");
            $this->getLogger()->info("Failed recipients: " . implode(',', $emailInst->getFailedRecipients()));
        }
        return $this->getController()->redirectBack();
    }
}
