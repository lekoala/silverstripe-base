<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\AssetAdmin\Forms\UploadField;

class BuildableFieldList extends FieldList
{
    /**
     * @var string
     */
    protected $i18nEntity = 'GLOBAL';

    /**
     * Returns an instance of BuildableFieldList from a FieldList
     *
     * @param FieldList $fields
     * @return self
     */
    public static function fromFieldList(FieldList $fields)
    {
        $arr = $fields->toArray();
        return new self($arr);
    }
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
            $fallback = FormField::name_to_label($name);
            // Attempt translation
            $validKey = str_replace(['[', ']', '_'], '', $name);
            $title = _t($this->i18nEntity . '.' . $validKey, $fallback);
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
            } else if ($k == 'description') {
                $object->setDescription($v);
            } else if ($k == 'options') {
                $object->setSource($v);
            } else {
                $object->setAttribute($k, $v);
            }
        }
        return $object;
    }

    /**
     * @param string $name
     * @return GridField
     */
    public function getGridField($name) {
        return $this->dataFieldByName($name);
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
     * @param array $src
     * @param array $attributes
     * @return DropdownField
     */
    public function addDropdown($name = "Option", $title = null, $src = [], $attributes = [])
    {
        $attributes['options'] = $src;
        return $this->addField(DropdownField::class, $name, $title, $attributes);
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


    /**
     * Get the value of i18nEntity
     */
    public function getI18nEntity()
    {
        return $this->i18nEntity;
    }

    /**
     * Set the value of i18nEntity
     *
     * @return  self
     */
    public function setI18nEntity($i18nEntity)
    {
        $this->i18nEntity = $i18nEntity;

        return $this;
    }
}
