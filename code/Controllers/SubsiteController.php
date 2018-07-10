<?php
namespace LeKoala\Base\Controllers;

use LeKoala\Base\Subsite\SubsiteHelper;

trait SubsiteController
{
    /**
     * @return int
     */
    public function getSubsiteId()
    {
        return SubsiteHelper::currentSubsiteID();
    }
}
