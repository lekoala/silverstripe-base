<?php

namespace LeKoala\Base\Blocks;

use LeKoala\Base\Blocks\Block;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use SilverStripe\View\ViewableData;
use LeKoala\Base\Helpers\ClassHelper;
use LeKoala\Base\Blocks\BlockFieldList;

/**
 * This is the class you need to extend to create your own block
 *
 * Also see BlocksCreateTask to create to blocks for you
 */
class BaseBlock extends ViewableData
{
    /**
     * @var Block
     */
    protected $_block;

    public function __construct(Block $block)
    {
        $this->_block = $block;
        $this->customise($block);
    }

    /**
     * Allow direct queries from the template
     *
     * Use wisely...
     *
     * @param string $class
     * @return DataList
     */
    public function Query($class)
    {
        // Allow unqualified classes
        if (!class_exists($class)) {
            $subclasses = ClassInfo::subclassesFor(DataObject::class);
            foreach ($subclasses as $lcName => $name) {
                if ($class == ClassHelper::getClassWithoutNamespace($name)) {
                    $class = $name;
                    break;
                }
            }
        }
        return $class::get();
    }


    public function updateFields(BlockFieldList $fields)
    {
    }

    public function updateClass(&$class)
    {
    }

    /**
     * Extra data to feed to the template
     * @return array
     */
    public function ExtraData()
    {
        return [];
    }

    public function Collection()
    {
    }

    public function SharedCollection()
    {
    }
}
