<?php

namespace LeKoala\Base\Blocks\Types;

use LeKoala\Base\Blocks\BaseBlock;
use LeKoala\Base\Blocks\BlockFieldList;

/**
 * The default block
 *
 * Add an editor after the image
 *
 * Do not use "Content" as it used to save rendered template
 */
class ContentBlock extends BaseBlock
{
    public function updateFields(BlockFieldList $fields)
    {
        $fields->addEditor();
    }
}
