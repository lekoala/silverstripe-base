<?php

namespace LeKoala\Base\Blocks;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use LeKoala\Base\Blocks\Fields\BlockButtonField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\CheckboxField;

class BlockFieldList extends FieldList
{
    protected $defaultTab = 'Main';
    protected $defaultKey = 'Data';

    protected function normalizeTitle($name, $title = null)
    {
        if ($title === null) {
            if (is_array($name)) {
                $name = $name[1];
            }
            $title = FormField::name_to_label($name);
        }
        return $title;
    }

    protected function normalizeName($name, $baseKey = null)
    {
        if ($baseKey === null) {
            $baseKey = $this->defaultKey;
        }
        if (is_array($name)) {
            $itemsKey = Block::ITEMS_KEY;
            $idx = $name[0];
            $key = $name[1];
            $name = $baseKey . '[' . $itemsKey . '][' . $idx . '][' . $key . ']';
        } else {
            $name = $baseKey . '[' . $name . ']';
        }
        return $name;
    }

    /**
     * Convenience methods to add settings
     *
     * @param callable $cb
     * @return void
     */
    public function addSettings($cb)
    {
        $tab = $this->getDefaultTab();
        $key = $this->getDefaultKey();
        $this->setDefaultTab('Settings');
        $this->setDefaultKey('Settings');

        $cb($this);

        $this->setDefaultTab($tab);
        $this->setDefaultKey($key);
    }

    public function addField($class, $name, $title)
    {
        $title = $this->normalizeTitle($name, $title);
        $name = $this->normalizeName($name);
        $field = $class::create($name, $title);
        $this->addFieldsToTab('Root.' . $this->defaultTab, $field);
        return $field;
    }

    public function addHeader($title, $level = 2)
    {
        static $i = 0;
        $i++;
        $field = HeaderField::create("H[$i]", $title, $level);
        $this->addFieldsToTab('Root.' . $this->defaultTab, $field);
        return $field;
    }

    public function addUpload($name = "ImageID", $title = null)
    {
        return $this->addField(UploadField::class, $name, $title);
    }

    public function addCheckbox($name = "IsEnabled", $title = null)
    {
        return $this->addField(CheckboxField::class, $name, $title);
    }

    public function addText($name = "Title", $title = null)
    {
        return $this->addField(TextField::class, $name, $title);
    }

    public function addTextarea($name = "Description", $title = null)
    {
        return $this->addField(TextareaField::class, $name, $title);
    }

    public function addButton($name = "Button", $title = null)
    {
        return $this->addField(BlockButtonField::class, $name, $title);
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
