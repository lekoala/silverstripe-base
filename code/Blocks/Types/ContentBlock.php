<?php
namespace LeKoala\Base\Blocks\Types;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use LeKoala\Base\Blocks\BaseBlock;

class ContentBlock extends BaseBlock
{
    public function updateFields(FieldList $fields)
    {
        $fields->insertBefore('Image', new HTMLEditorField('Content'));
    }
}
