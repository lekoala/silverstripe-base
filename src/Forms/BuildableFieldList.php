<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use LeKoala\Base\Forms\ColumnsField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\NumericField;

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
     * @var FieldGroup
     */
    protected $currentGroup = null;

    /**
     * The entity scope that will be used to attempt translation
     * @var string
     */
    protected $i18nEntity = 'Global';

    /**
     * @var boolean
     */
    protected $placeholderAsLabel = false;

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
            } elseif ($k == 'empty') {
                $object->setHasEmptyDefault($v);
            } else {
                $object->setAttribute($k, $v);
            }
        }
        if ($this->placeholderAsLabel) {
            $object->setAttribute('placeholder', $object->Title());
            $object->setTitle('');
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
        } elseif ($this->currentGroup) {
            $this->currentGroup->push($field);
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
     * @return ReadonlyField
     */
    public function addReadonly($name, $title = null, $attributes = [])
    {
        return $this->addField(ReadonlyField::class, $name, $title, $attributes);
    }

    /**
     * @param string $content
     * @param string $name
     * @param string $type
     * @return AlertField
     */
    public function addAlert($content, $type = null, $name = null)
    {
        static $i = 0;
        if ($name === null) {
            $i++;
            $name = "A_$i";
        }
        $field = AlertField::create($name, $content, $type);
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
     * @return FilePondField
     */
    public function addSingleFilePond($name = "ImageID", $title = null, $attributes = [])
    {
        $fp = $this->addField(FilePondField::class, $name, $title, $attributes);
        $fp->setAllowedMaxFileNumber(1);
        return $fp;
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return InputMaskField
     */
    public function addInputMask($name, $title = null, $attributes = [])
    {
        return $this->addField(InputMaskField::class, $name, $title, $attributes);
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
     * @param array $src
     * @param array $attributes
     * @return CheckboxSetField
     */
    public function addCheckboxset($name = "Options", $title = null, $src = [], $attributes = [])
    {
        $attributes['options'] = $src;
        return $this->addField(CheckboxSetField::class, $name, $title, $attributes);
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
     * @param array $attributes Special attrs : empty, source
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
     * @param array $src
     * @param array $attributes
     * @return OptionsetField
     */
    public function addOptionset($name = "Option", $title = null, $src = [], $attributes = [])
    {
        $attributes['options'] = $src;
        return $this->addField(OptionsetField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return DateField
     */
    public function addDate($name = "Date", $title = null, $attributes = [])
    {
        return $this->addField(DateField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $src
     * @param array $attributes
     * @return InputMaskDateField
     */
    public function addDateMask($name = "BirthDate", $title = null, $attributes = [])
    {
        return $this->addField(InputMaskDateField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $src
     * @param array $attributes
     * @return InputMaskNumericField
     */
    public function addNumericMask($name = "Number", $title = null, $attributes = [])
    {
        return $this->addField(InputMaskNumericField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $src
     * @param array $attributes
     * @return InputMaskCurrencyField
     */
    public function addCurrencyMask($name = "Amount", $title = null, $attributes = [])
    {
        return $this->addField(InputMaskCurrencyField::class, $name, $title, $attributes);
    }
    /**
     * @param string $name
     * @param string $title
     * @param array $src
     * @param array $attributes
     * @return InputMaskDateField
     */
    public function addIntegerMask($name = "Number", $title = null, $attributes = [])
    {
        return $this->addField(InputMaskIntegerField::class, $name, $title, $attributes);
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
     * @return NumericField
     */
    public function addNumeric($name = "Number", $title = null, $attributes = [])
    {
        return $this->addField(NumericField::class, $name, $title, $attributes);
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
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return HTMLEditorField
     */
    public function addEditor($name = "Description", $title = null, $attributes = [])
    {
        return $this->addField(HTMLEditorField::class, $name, $title, $attributes);
    }

    /**
     * Group fields into a column field
     *
     * @param callable $callable
     * @return $this
     */
    public function group($callable)
    {
        $group = new ColumnsField();
        $this->pushOrAddToTab($group);
        $this->currentGroup = $group;
        $callable($this);
        $this->currentGroup = null;
        return $this;
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
     * @return $this
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
     * @return $this
     */
    public function setDefaultTab($defaultTab)
    {
        $this->defaultTab = $defaultTab;
        return $this;
    }

    /**
     * Get the value of placeholderAsLabel
     * @return boolean
     */
    public function getPlaceholderAsLabel()
    {
        return $this->placeholderAsLabel;
    }

    /**
     * Set the value of placeholderAsLabel
     *
     * @param boolean $placeholderAsLabel
     * @return $this
     */
    public function setPlaceholderAsLabel($placeholderAsLabel)
    {
        $this->placeholderAsLabel = $placeholderAsLabel;
        return $this;
    }
}
