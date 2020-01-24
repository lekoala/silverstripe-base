<?php

namespace LeKoala\Base\Blocks\Types;

use LeKoala\Base\Blocks\BaseBlock;
use LeKoala\Base\Blocks\BlockFieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class ContentBlock extends BaseBlock
{
    public function updateFields(BlockFieldList $fields)
    {
        $fields->insertBefore('Image', new HTMLEditorField('Content'));
    }
}
