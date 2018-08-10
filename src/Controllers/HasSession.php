<?php

namespace LeKoala\Base\Controllers;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;

/**
 * Trait add a static getter. We use static because we don't now if we have a context
 * and we don't use instance anyway
 *
 * @link https://docs.silverstripe.org/en/4/developer_guides/cookies_and_sessions/sessions/
 */
trait HasSession
{
    /**
     * @return LoggerInterface
     */
    public static function getSession()
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        return $request->getSession();
    }
}
