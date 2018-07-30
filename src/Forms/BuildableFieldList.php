<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\GridField\GridFieldConfig;

/**
 * A field list that can create it its fields
 */
class BuildableFieldList extends FieldList
{
    /**
     * @var string
     */
    protected $defaultTab = null;

    /**
     * @var string
     */
    protected $currentTab = null;

    /**
     * The entity scope that will be used to attempt translation
     * @var string
     */
    protected $i18nEntity = 'Global';

    /**
     * Returns an instance of BuildableFieldList from a FieldList
     *
     * @param FieldList $fields
     * @return $this
     */
    public static function fromFieldList(FieldList $fields = null)
    {
        if ($fields === null) {
            return new self();
        }
        $arr = $fields->toArray();
        return new self($arr);
    }

    /**
     * Slightly improve way to normalize titles in forms
     *
     * @param string $name
     * @param string $title
     * @return string
     */
    protected function normalizeTitle($name, $title = "")
    {
        if ($title === null) {
            if (is_array($name)) {
                $name = $name[1];
            }
            $fallback = FormField::name_to_label($name);
            $fallback = str_replace(['[', ']', '_'], ' ', $fallback);
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
            } elseif ($k == 'description') {
                $object->setDescription($v);
            } elseif ($k == 'options') {
                $object->setSource($v);
            } else {
                $object->setAttribute($k, $v);
            }
        }
        return $object;
    }

    protected function pushOrAddToTab($field)
    {
        if (!$this->currentTab && $this->defaultTab) {
            $this->currentTab = $this->defaultTab;
        }
        if ($this->currentTab) {
            $this->addFieldToTab('Root.' . $this->currentTab, $field);
        } else {
            $this->push($field);
        }
    }

    /**
     * @param string $name
     * @return GridField
     */
    public function getGridField($name)
    {
        $gridfield = $this->dataFieldByName($name);
        if (!$gridfield || !$gridfield instanceof GridField) {
            return null;
        }
        return $gridfield;
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
     * Push a FormField already defined
     *
     * @param FormField $field
     * @return FormField
     */
    public function pushField(FormField $field)
    {
        $this->pushOrAddToTab($field);
        return $field;
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
        $this->pushOrAddToTab($field);
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
        $field = HeaderField::create("H_$i", $title, $level);
        $this->pushOrAddToTab($field);
        return $field;
    }

    /**
     * @param string $content
     * @param string $name
     * @return LiteralField
     */
    public function addLiteral($content, $name = null)
    {
        static $i = 0;
        if ($name === null) {
            $i++;
            $name = "L_$i";
        }
        $field = LiteralField::create($name, $content);
        $this->pushOrAddToTab($field);
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
     * @return FilePondField
     */
    public function addFilePond($name = "ImageID", $title = null, $attributes = [])
    {
        return $this->addField(FilePondField::class, $name, $title, $attributes);
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
     * @param array $attributes
     * @return HiddenField
     */
    public function addHidden($name = "ID", $attributes = [])
    {
        return $this->addField(HiddenField::class, $name, null, $attributes);
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

    /**
     * Get the value of defaultTab
     */
    public function getDefaultTab()
    {
        return $this->defaultTab;
    }

    /**
     * Set the value of defaultTab
     *
     * @return  self
     */
    public function setDefaultTab($defaultTab)
    {
        $this->defaultTab = $defaultTab;
        return $this;
    }

    /**
     * Get the value of defaultKey
     */
    public function getDefaultKey()
    {
        return $this->defaultKey;
    }

    /**
     * Set the value of defaultKey
     *
     * @return  self
     */
    public function setDefaultKey($defaultKey)
    {
        $this->defaultKey = $defaultKey;
        return $this;
    }
}
