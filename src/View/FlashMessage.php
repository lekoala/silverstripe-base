<?php

namespace LeKoala\Base\View;

use Exception;
use SilverStripe\i18n\i18n;
use LeKoala\Base\View\Alertify;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Session;
use SilverStripe\View\Requirements;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;

/**
 * Use Alertify or Sweetalert to show flash message
 */
class FlashMessage
{
    use Configurable;

    /**
     * @config
     * @var string
     */
    private static $provider = Alertify::class;

    /**
     * Display an alert only once (check is cookie based)
     *
     * @param string $name The name of the notification (name will be stored in a cookie)
     * @param string $message
     * @param string $type
     * @return bool Has the notification been displayed?
     */
    public static function notifyOnce($name, $message, $type)
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        $session = $request->getSession();
        if ($session) {
            $check = $session->get($name);
        } else {
            $check = Cookie::get($name);
        }
        if ($check) {
            return false;
        }
        $provider = self::config()->provider;
        $provider::requirements();
        $provider::show($message, $type);
        if ($session) {
            $session->set($name, 1);
        } else {
            Cookie::set($name, 1);
        }
        return true;
    }

    /**
     * Display the flash message if any using Alertifyjs
     *
     * @param Session $session
     * @return void
     */
    public static function checkFlashMessage($session)
    {
        try {
            $FlashMessage = $session->get('FlashMessage');
        } catch (Exception $ex) {
            $FlashMessage = null; // Session can be null (eg : Security)
        }
        if (!$FlashMessage) {
            return;
        }
        $session->clear('FlashMessage');
        $provider = self::config()->provider;
        $provider::requirements();
        $provider::show($FlashMessage['Message'], $FlashMessage['Type']);
    }
}
