<?php

namespace LeKoala\Base\Helpers;

use SilverStripe\ORM\ArrayLib;
use SilverStripe\Control\Director;

class StylesHelper
{
    public static function stylesList($file, $prefix)
    {

        $content = file_get_contents(Director::baseFolder() . $file);
        $matches = null;
        preg_match_all('/'.$prefix.'([a-z\-0-9]+)/', $content, $matches);

        $list = ArrayLib::valuekey($matches[1]);

        return $list;
    }
}
