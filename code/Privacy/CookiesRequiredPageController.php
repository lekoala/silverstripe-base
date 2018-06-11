<?php

namespace LeKoala\Base\Privacy;

use PageController;
use LeKoala\Base\View\CookieConsent;
use SilverStripe\Control\HTTPRequest;

class CookiesRequiredPageController extends PageController
{
    public function init()
    {
        parent::init();
        CookieConsent::clearRequirements();
    }

    public function accept(HTTPRequest $request)
    {
        CookieConsent::forceAllow();
        return $this->redirect('/');
    }
}
