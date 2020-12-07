<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use LeKoala\Base\Forms\ColumnsField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\CheckboxSetField;
use LeKoala\Base\Forms\YesNoOptionsetField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FileField;

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
            // For items list like [idx, name]
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
            } elseif ($k == 'value') {
                $object->setValue($v);
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
        // if we have a default tab set, make sure it's set to active
        if (!$this->currentTab && $this->defaultTab) {
            $this->currentTab = $this->defaultTab;
        }
        // Groups have priority
        if ($this->currentGroup) {
            $this->currentGroup->push($field);
        } elseif ($this->currentTab) {
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
     * @param string $name Name without id since it's used as $record->{"{$fieldname}ID"} = $id;
     * @param string $title
     * @param array $attributes
     * @return UploadField
     */
    public function addUpload($name = "Image", $title = null, $attributes = [])
    {
        return $this->addField(UploadField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name Name without id since it's used as $record->{"{$fieldname}ID"} = $id;
     * @param string $title
     * @param array $attributes
     * @return FileField
     */
    public function addFile($name = "Image", $title = null, $attributes = [])
    {
        return $this->addField(FileField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name Name without id since it's used as $record->{"{$fieldname}ID"} = $id;
     * @param string $title
     * @param array $attributes
     * @return FilePondField
     */
    public function addFilePond($name = "Image", $title = null, $attributes = [])
    {
        return $this->addField(FilePondField::class, $name, $title, $attributes);
    }

    /**
     * @param string $name Name without id since it's used as $record->{"{$fieldname}ID"} = $id;
     * @param string $title
     * @param array $attributes
     * @return FilePondField
     */
    public function addSingleFilePond($name = "Image", $title = null, $attributes = [])
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
     * @param array $attributes
     * @param string $name
     * @return FieldGroup
     */
    public function addFieldGroup($attributes = [], $name = null)
    {
        static $i = 0;
        if ($name === null) {
            $i++;
            $name = "Group_$i";
        }
        return $this->addField(FieldGroup::class, $name, null, $attributes);
    }

    /**
     * @param array $attributes
     * @param string $name
     * @return CompositeField
     */
    public function addCompositeField($attributes = [], $name = null)
    {
        static $i = 0;
        if ($name === null) {
            $i++;
            $name = "Composite_$i";
        }
        return $this->addField(CompositeField::class, $name, null, $attributes);
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
     * @return YesNoOptionsetField
     */
    public function addYesNo($name = "Option", $title = null, $attributes = [])
    {
        return $this->addField(YesNoOptionsetField::class, $name, $title, $attributes);
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
     * @return PhoneField
     */
    public function addPhone($name = "Phone", $title = null, $attributes = [])
    {
        return $this->addField(PhoneField::class, $name, $title, $attributes);
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
     * Usage is something like this
     *
     *  $fields->group(function (BuildableFieldList $fields) {
     *      $fields->addText('Item1');
     *      $fields->addText('Item2');
     * });
     *
     * @param callable $callable
     * @param array $columnSizes Eg: [1 => 4, 2 => 8]
     * @return $this
     */
    public function group($callable, $columnSizes = null)
    {
        $group = new ColumnsField();
        if ($columnSizes !== null) {
            $group->setColumnSizes($columnSizes);
        }
        // First push the group
        $this->pushOrAddToTab($group);
        // Then set it as current (don't do the opposite, otherwise pushOrAddToTab doesn't work)
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
     * Get the value of currentTab
     */
    public function getCurrentTab()
    {
        return $this->currentTab;
    }

    /**
     * The current tab
     *
     * @return $this
     */
    public function setCurrentTab($currentTab)
    {
        $this->currentTab = $currentTab;
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
     * The default tab if there is no current tab
     *
     * This only apply before any field is added. After that it's better to use setCurrentTab
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
