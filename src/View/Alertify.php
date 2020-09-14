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
 * @link http://alertifyjs.com
 */
class Alertify
{
    use Configurable;

    /**
     * default,bootstrap,semantic
     * @config
     * @var string
     */
    private static $theme = 'default';

    /**
     * @config
     * @var string
     */
    private static $version = '1.13.1';

    /**
     * @config
     * @var bool
     */
    private static $use_alerts = false;

    /**
     * @config
     * @var array
     */
    private static $defaults = [
        'notifier.position' => "top-center",
        'notifier.delay' => "5",
        'transition' => "zoom",
        'theme.ok' => "btn btn-primary",
        'theme.cancel' => "btn btn-danger",
        'theme.input' => "form-control",
    ];

    /**
     * Add AlertifyJS requirements
     */
    public static function requirements()
    {
        $theme = self::config()->theme;
        $version = self::config()->version;

        Requirements::javascript('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/' . $version . '/alertify.min.js');
        $dir = i18n::get_script_direction();
        if ($dir == 'rtl') {
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/' . $version . '/css/alertify.rtl.min.css');
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/' . $version . '/css/themes/' . $theme . '.rtl.min.css');
        } else {
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/' . $version . '/css/alertify.min.css');
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/' . $version . '/css/themes/' . $theme . '.min.css');
        }
        $settings = '';
        foreach (self::config()->defaults as $k => $v) {
            $settings .= "alertify.defaults.$k = '$v';\n";
        }
        Requirements::customScript($settings, 'AlertifySettings');
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

    public static function show($message, $type, $asAlert = null)
    {
        if ($asAlert === null) {
            $asAlert = self::config()->use_alerts;
        }
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
        if ($asAlert) {
            $js = "alertify.alert('$msg').set(transition:'zoom', basic:true, movable:true);";
            $js = "alertify.notify('$msg', '$type', 0);";
        } else {
            $js = "alertify.notify('$msg', '$type', 0);";
        }

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
