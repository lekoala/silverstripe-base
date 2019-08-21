<?php
namespace LeKoala\Base\Blocks;

use PageController;

/**
 * Class \LeKoala\Base\Blocks\BlocksPageController
 *
 * @property \LeKoala\Base\Blocks\BlocksPage dataRecord
 * @method \LeKoala\Base\Blocks\BlocksPage data()
 * @mixin \LeKoala\Base\Blocks\BlocksPage dataRecord
 */
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
