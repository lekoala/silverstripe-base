<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Geo\Models\Address;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use LeKoala\Base\Geo\Services\Geolocator;

/**
 * Class \LeKoala\Base\Extensions\IPExtension
 *
 * @property \LeKoala\Base\Security\MemberAudit|\LeKoala\Base\Extensions\IPExtension $owner
 * @property string $IP
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
        $graphloc = Injector::inst()->get(Geolocator::class);
        if (!$this->owner->IP) {
            return false;
        }
        return $graphloc->geolocate($this->owner->IP);
    }
}
