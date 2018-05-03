<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use LeKoala\Base\Services\Graphloc;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Geo\Models\Address;
use SilverStripe\Control\Controller;
use LeKoala\Base\ORM\FieldType\IPAddress;

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
    /**
     * @return Address
     */
    public function getIpLocationDetails()
    {
        $graphloc = new Graphloc;
        if (!$this->owner->Ip) {
            return false;
        }
        return $graphloc->get($this->owner->Ip);
    }
}
