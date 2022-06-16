<?php

namespace LeKoala\Base\Admin;

use LeKoala\Admini\LeftAndMainExtension;
use SilverStripe\View\Requirements;

class BaseAdminiExtension extends LeftAndMainExtension
{

    public function init()
    {
        Requirements::javascript("https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js");
    }
}
