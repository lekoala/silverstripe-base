<?php

namespace LeKoala\Base\Dev;

use LeKoala\Base\Dev\BasicAuth;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;

class EnvironmentChecker
{
    /**
     * @var Controller
     */
    protected $controller;

    public function check($controller)
    {
        $this->controller = $controller;

        // Properly protect in test mode
        // if (Director::isTest()) {
        //     BasicAuth::protect();
        // }

        $this->warnIfWrongCacheIsUsed();

        // A few helpful things in dev mode
        if (Director::isDev()) {
            $this->ensureTempFolderExists();
            if ($this->isLocalIp()) {
                $this->allowAutologin();
            }
        }
    }

    /**
     * @return boolean
     */
    public function isLocalIp()
    {
        return in_array(Controller::curr()->getRequest()->getIP(), ['127.0.0.1', '::1', '1']);
    }

    /**
     * Easily login on dev sites
     * Do not run this on production
     *
     * @return void
     */
    protected function allowAutologin()
    {
        $request = $this->controller->getRequest();
        if ($request->getVar('autologin')) {
            $admin = Security::findAnAdministrator();
            // $admin->login() is deprecated
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($admin, true, $request);
        }
    }

    /**
     * Because you really should! Speed increase by a 2x magnitude
     *
     * @return void
     */
    protected function warnIfWrongCacheIsUsed()
    {
        if ($this->controller->getCache() instanceof Symfony\Component\Cache\Simple\FilesystemCache) {
            $this->controller->getLogger()->info("OPCode cache is not enabled. To get maximum performance, enable it in php.ini");
        }
    }

    /**
     * Temp folder should always be there
     *
     * @return void
     */
    protected function ensureTempFolderExists()
    {
        $tempFolder = Director::baseFolder() . '/silverstripe-cache';
        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0755);
        }
    }
}
