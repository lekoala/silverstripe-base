<?php

use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use LeKoala\Base\Geo\Services\Geolocator;

/**
 * Store IP as binary
 *
 * @link https://stackoverflow.com/questions/22636912/store-both-ipv4-and-ipv6-address-in-a-single-column
 * @property \BinaryIPExtension $owner
 * @property string $IP
 */
class BinaryIPExtension extends DataExtension
{
    private static $db = [
        "IP" => "BinaryIP"
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
