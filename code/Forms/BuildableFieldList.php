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
     * Apply attributes to a form object
     *
     * @param FormField $object
     * @param array $attributes
     * @return FormField
     */
    protected function applyAttributes($object, $attributes)
    {
        foreach ($attributes as $k => $v) {
            if ($k == 'class') {
                $object->addExtraClass($v);
            } else {
                $object->setAttribute($v);
            }
        }
        return $object;
    }

    /**
     * Quickly add an action to a list
     *
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return FormAction
     */
    public function addAction($name, $title = "", $attributes = [])
    {
        return $this->addField(FormAction::class, $name, $title, $attributes);
    }

    /**
     * Add a field to the list
     *
     * @param string $class
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return FormField
     */
    public function addField($class, $name, $title = "", $attributes = [])
    {
        $title = $this->normalizeTitle($name, $title);
        $field = $class::create($name, $title);
        $field = $this->applyAttributes($field, $attributes);
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
     * @param array $attributes
     * @return UploadField
     */
    public function addUpload($name = "ImageID", $title = null, $attributes = [])
    {
        return $this->addField(UploadField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return CheckboxField
     */
    public function addCheckbox($name = "IsEnabled", $title = null, $attributes = [])
    {
        return $this->addField(CheckboxField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return PasswordField
     */
    public function addPassword($name = "Password", $title = null, $attributes = [])
    {
        return $this->addField(PasswordField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return EmailField
     */
    public function addEmail($name = "Email", $title = null, $attributes = [])
    {
        return $this->addField(EmailField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return TextField
     */
    public function addText($name = "Title", $title = null, $attributes = [])
    {
        return $this->addField(TextField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return TextareaField
     */
    public function addTextarea($name = "Description", $title = null, $attributes = [])
    {
        return $this->addField(TextareaField::class, $name, $title, $attributes);
    }

}
