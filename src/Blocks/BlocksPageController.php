<?php
namespace LeKoala\Base\Blocks;

use PageController;

class BlocksPageController extends PageController
{
    public function BodyClass()
    {
        $class = parent::BodyClass();

        $arr = $this->data()->getBlocksListArray();
        if (!empty($arr)) {
            $class .= ' Starts-' . $arr[0];
        }

        return $class;
    }
}
