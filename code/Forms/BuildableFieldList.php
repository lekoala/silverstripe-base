<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\PasswordField;

class BuildableFieldList extends FieldList
{
    /**
     * Slightly improve way to normalize titles in forms
     *
     * @param string $name
     * @param string $title
     * @return strin
     */
    protected function normalizeTitle($name, $title = "")
    {
        if ($title === null) {
            if (is_array($name)) {
                $name = $name[1];
            }
            $title = FormField::name_to_label($name);
        }
        return $title;
    }

    /**
     * Quickly add an action to a list
     *
     * @param string $name
     * @param string $title
     * @return FormAction
     */
    public function addAction($name, $title = "")
    {
        $title = $this->normalizeTitle($name, $title);
        $action = new FormAction($name, $title);
        $this->push($action);
    }

    /**
     * Add a field to the list
     *
     * @param string $class
     * @param string $name
     * @param string $title
     * @return void
     */
    public function addField($class, $name, $title = "")
    {
        $title = $this->normalizeTitle($name, $title);
        $field = $class::create($name, $title);
        $this->push($field);
        return $field;
    }

    /**
     * @param string $name
     * @param string $title
     * @return HeaderField
     */
    public function addHeader($title, $level = 2)
    {
        static $i = 0;
        $i++;
        $field = HeaderField::create("H[$i]", $title, $level);
        $this->addFieldsToTab('Root.' . $this->defaultTab, $field);
        return $field;
    }

    /**
     * @param string $name
     * @param string $title
     * @return UploadField
     */
    public function addUpload($name = "ImageID", $title = null)
    {
        return $this->addField(UploadField::class, $name, $title);
    }

    /**
     * @param string $name
     * @param string $title
     * @return CheckboxField
     */
    public function addCheckbox($name = "IsEnabled", $title = null)
    {
        return $this->addField(CheckboxField::class, $name, $title);
    }

    /**
     * @param string $name
     * @param string $title
     * @return PasswordField
     */
    public function addPassword($name = "Password", $title = null)
    {
        return $this->addField(PasswordField::class, $name, $title);
    }

    /**
     * @param string $name
     * @param string $title
     * @return EmailField
     */
    public function addEmail($name = "Email", $title = null)
    {
        return $this->addField(EmailField::class, $name, $title);
    }

    /**
     * @param string $name
     * @param string $title
     * @return TextField
     */
    public function addText($name = "Title", $title = null)
    {
        return $this->addField(TextField::class, $name, $title);
    }

    /**
     * @param string $name
     * @param string $title
     * @return TextareaField
     */
    public function addTextarea($name = "Description", $title = null)
    {
        return $this->addField(TextareaField::class, $name, $title);
    }

}
