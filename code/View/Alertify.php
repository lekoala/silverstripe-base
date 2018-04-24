<?php

namespace LeKoala\Base\View;

use SilverStripe\i18n\i18n;
use SilverStripe\Control\Session;
use SilverStripe\View\Requirements;
use SilverStripe\Core\Config\Configurable;

/**
 *
 * @link http://alertifyjs.com
 */
class Alertify
{
    use Configurable;

    /**
     * @var string
     */
    private static $theme = 'bootstrap';

    /**
     * Add AlertifyJS requirements
     */
    public static function requirements()
    {
        $theme = self::config()->theme;

        Requirements::javascript('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.1/alertify.min.js', ['defer' => true]);
        $dir = i18n::get_script_direction();
        if ($dir == 'rtl') {
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.1/css/alertify.rtl.min.css');
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.1/css/themes/' . $theme . '.rtl.min.css');
        } else {
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.1/css/alertify.min.css');
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.1/css/themes/' . $theme . '.min.css');
        }
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
        }
        $settings = <<<JS
alertify.defaults.transition = "zoom";
alertify.defaults.theme.ok = "btn btn-primary";
alertify.defaults.theme.cancel = "btn btn-danger";
alertify.defaults.theme.input = "form-control";
JS;
        Requirements::customScript($settings, 'AlertifySettings');
        $js = "window.addEventListener('DOMContentLoaded', function() {alertify.notify('$msg', '$type', 0); });";
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
