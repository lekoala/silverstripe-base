<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use LeKoala\Base\Services\Graphloc;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Geo\Models\Address;
use SilverStripe\Control\Controller;
use LeKoala\Base\ORM\FieldType\IPAddress;
use LeKoala\DebugBar\Controller;

/**
 *
 */
class IPExtension extends DataExtension
{
    private static $db = [
        "IP" => "Varchar(45)"
    ];
    public function onBeforeWrite()
    {
        $controller = Controller::curr();
        // This is best used when IP is set on creation
        if (!$this->owner->IP) {
            $ip = $controller->getRequest()->getIP();
            $this->owner->IP = $ip;
        }
    }
    /**
     * @return Address
     */
    public function getIpLocationDetails()
    {
        $graphloc = new Graphloc;
        if (!$this->owner->IP) {
            return false;
        }
        return $graphloc->get($this->owner->IP);
    }
}
