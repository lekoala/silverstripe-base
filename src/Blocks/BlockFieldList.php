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
use LeKoala\Base\Forms\BuildableFieldList;

/**
 * Easily add fields to your blocks
 */
class BlockFieldList extends BuildableFieldList
{
    /**
     * @var string
     */
    protected $defaultTab = 'Main';

    /**
     * @var string
     */
    protected $defaultKey = 'BlockData';

    /**
     * Automatically expand the given name to match the default key
     * and items if necessary
     *
     * @param string $name
     * @param string $baseKey Will use defaultKey if not provided
     * @return string
     */
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

    protected function normalizeTitle($name, $title = "")
    {
        $name = str_replace([$this->defaultKey . '[', ']'], '', $name);
        return parent::normalizeTitle($name, $title);
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
        $name = $this->normalizeName($name);
        return parent::addField($class, $name, $title);
    }

    /**
     * @param string $name
     * @param string $title
     * @return BlockButtonField
     */
    public function addButton($name = "Button", $title = null)
    {
        return $this->addField(BlockButtonField::class, $name, $title);
    }
}
