<?php

namespace LeKoala\Base\Privacy;

use PageController;
use LeKoala\Base\View\CookieConsent;
use SilverStripe\Control\HTTPRequest;

/**
 * Class \LeKoala\Base\Privacy\CookiesRequiredPageController
 *
 * @property \LeKoala\Base\Privacy\CookiesRequiredPage dataRecord
 * @method \LeKoala\Base\Privacy\CookiesRequiredPage data()
 * @mixin \LeKoala\Base\Privacy\CookiesRequiredPage dataRecord
 */
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
