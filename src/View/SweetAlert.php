<?php

namespace LeKoala\Base\View;

use Exception;
use SilverStripe\i18n\i18n;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Session;
use SilverStripe\View\Requirements;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;

/**
 * @link https://sweetalert2.github.io/
 */
class SweetAlert
{
    use Configurable;

    /**
     * @config
     * @var string
     */
    private static $theme = 'borderless';

    /**
     * @config
     * @var string
     */
    private static $theme_version = '3';

    /**
     * @config
     * @var string
     */
    private static $version = '9';

    /**
     * Add AlertifyJS requirements
     */
    public static function requirements()
    {
        $theme = self::config()->theme;
        $theme_version = self::config()->theme_version;
        $version = self::config()->version;

        Requirements::javascript('https://cdn.jsdelivr.net/npm/sweetalert2@' . $version . '/dist/sweetalert2.min.js');
        //https://cdn.jsdelivr.net/npm/@sweetalert2/themes@3.1.4/minimal/minimal.css
        Requirements::css('https://cdn.jsdelivr.net/npm/@sweetalert2/themes@' . $theme_version . '/' . $theme . '/' . $theme . '.css');
    }

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
        self::requirements();
        self::show($message, $type);
        if ($session) {
            $session->set($name, 1);
        } else {
            Cookie::set($name, 1);
        }
        return true;
    }

    public static function show($message, $type)
    {
        $msg = addslashes($message);
        $type = $type;
        switch ($type) {
            case 'good':
                $type = 'success';
                break;
            case 'bad':
                $type = 'error';
                break;
            case 'warn':
                $type = 'warning';
                break;
        }
        $js = "Swal.fire({text:'$msg',icon:'$type'})";
        Requirements::customScript($js);
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
        self::requirements();
        self::show($FlashMessage['Message'], $FlashMessage['Type']);
    }
}
