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

    protected function normalizeName($name)
    {
        if (is_array($name)) {
            $itemsKey = Block::ITEMS_KEY;
            $idx = $name[0];
            $key = $name[1];
            $name = "Data[$itemsKey][$idx][$key]";
        } else {
            $name = "Data[$name]";
        }
        return $name;
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
        $this->addFieldsToTab('Root.Main', $field);
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
}
