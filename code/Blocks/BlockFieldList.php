<?php

namespace LeKoala\Base\Blocks;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use LeKoala\Base\Blocks\Fields\BlockButtonField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;

class BlockFieldList extends FieldList
{
    protected function normalizeTitle($name, $title = null)
    {
        if ($title === null) {
            $title = FormField::name_to_label($name);
        }
        return $title;
    }

    public function addText($name = "Title", $title = null)
    {
        $title = $this->normalizeTitle($name, $title);
        $field = new TextField("Data[$name]", $title);
        $this->addFieldsToTab('Root.Main', $field);
        return $field;
    }

    public function addTextarea($name = "Description", $title = null)
    {
        $title = $this->normalizeTitle($name, $title);
        $field = new TextareaField("Data[$name]", $title);
        $this->addFieldsToTab('Root.Main', $field);
        return $field;
    }

    public function addButton($name = "Button", $title = null)
    {
        $title = $this->normalizeTitle($name, $title);
        $field = new BlockButtonField("Data[$name]", $title);
        $this->addFieldsToTab('Root.Main', $field);
        return $field;
    }
}
