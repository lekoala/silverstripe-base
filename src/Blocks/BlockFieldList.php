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
use LeKoala\Base\Blocks\Fields\BlockHTMLEditorField;

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
     * @param string|array $name The key or a composite key like [2, "FieldName"].
     * @param string $baseKey Will use defaultKey if not provided. Use '' for no key.
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
            if ($baseKey) {
                $name = $baseKey . '[' . $name . ']';
            }
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
     * Supports adding items from lists which will be available
     * under the "Items" list
     *
     * see : $data[self::ITEMS_KEY] = self::normalizeIndexedList($data[self::ITEMS_KEY]);
     *
     * @param string $class
     * @param string|array $name Pass an array as [$idx, $name] to specify an item from a list
     * @param string $title
     * @param array $attributes
     * @return FormField
     */
    public function addField($class, $name, $title = "", $attributes = [])
    {
        $name = $this->normalizeName($name);
        return parent::addField($class, $name, $title, $attributes);
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

    /**
     * @param string $name
     * @param string $title
     * @param array $attributes
     * @return BlockHTMLEditorField
     */
    public function addEditor($name = "Description", $title = null, $attributes = [])
    {
        return $this->addField(BlockHTMLEditorField::class, $name, $title, $attributes);
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
     * @return $this
     */
    public function setDefaultKey($defaultKey)
    {
        $this->defaultKey = $defaultKey;
        return $this;
    }

    /**
     * @param string $key
     * @param callable $callable
     * @return $this
     */
    public function withKey($key, $callable)
    {
        $defaultKey = $this->getDefaultKey();
        $this->setDefaultKey($key);
        $callable($this);
        $this->setDefaultKey($defaultKey);
        return $this;
    }
}
