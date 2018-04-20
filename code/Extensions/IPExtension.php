<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\ORM\FieldType\IPAddress;
use SilverStripe\Control\Controller;
use SilverStripe\Admin\LeftAndMain;

/**
 *
 */
class IPExtension extends DataExtension
{
    private static $db = [
        "Ip" => "Varchar(45)"
    ];
    public function onBeforeWrite()
    {
        $controller = Controller::curr();
        if ($controller instanceof LeftAndMain) {
            return;
        }
        $ip = $controller->getRequest()->getIP();
        $this->owner->Ip = $ip;
    }
}
