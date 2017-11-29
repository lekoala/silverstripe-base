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

class BaseBlock extends ViewableData
{
    protected $_block;

    public function __construct(Block $block)
    {
        $this->_block = $block;
        $this->customise($block);
    }

    /**
     * Get an image defined in data
     *
     * Also ensures that this image is published because ownership will not work properly
     *
     * @param int|string $ID id or key or array like { "Files": [ "int" ] }
     * @return Image
     */
    public function ImageByID($ID)
    {
        if (!\is_numeric($ID)) {
            if (\is_string($ID)) {
                $ID = $this->_block->getInData($ID);
            }
            if (\is_array($ID)) {
                $ID = $ID['Files'][0];
            }
            if (\is_object($ID)) {
                $ID = $ID->Files[0];
            }
        }
        $image = Image::get()->byID($ID);

        // This is just annoying
        if (!$image->isPublished()) {
            $image->doPublish();
        }

        return $image;
    }

    /**
     * Allow direct queries from the template
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


    public function updateFields(FieldList $fields)
    {
    }

    public function Collection()
    {
    }

    public function SharedCollection()
    {
    }

}
