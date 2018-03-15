<?php
namespace LeKoala\Base;

use \Exception;
use SilverStripe\i18n\i18n;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\View\Requirements;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\CMS\Controllers\ContentController as DefaultController;
/**
 * A more opiniated base controller for your app
 *
 */
class ContentController extends DefaultController
{
    /**
     * Inject public dependencies into the controller
     *
     * @var array
     */
    private static $dependencies = [
        'logger' => '%$Psr\Log\LoggerInterface',
        'cache' => '%$Psr\SimpleCache\CacheInterface.myCache', // see _config/cache.yml
    ];
    /**
     * @var Psr\Log\LoggerInterface
     */
    public $logger;
    /**
     * @var Psr\SimpleCache\CacheInterface
     */
    public $cache;

    protected function init()
    {
        if (Director::isTest()) {
            $this->requireHttpBasicAuth();
        }
        parent::init();
        $this->warnIfWrongCacheIsUsed();
        $this->displayFlashMessage();
    }

    public function BodyClass()
    {
        $parts = explode('\\', get_class($this->data()));
        return end($parts);
    }

    /**
     * A simple way to http protected a website (for staging for instance)
     * This is required because somehow the default mechanism shipped with SilverStripe is
     * not working properly
     *
     * @return void
     */
    protected function requireHttpBasicAuth()
    {
        $user = Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME');
        $password = Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        $hasSuppliedCredentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
        if ($hasSuppliedCredentials) {
            $isNotAuthenticated = ($_SERVER['PHP_AUTH_USER'] != $user || $_SERVER['PHP_AUTH_PW'] != $password);
        } else {
            $isNotAuthenticated = true;
        }
        if ($isNotAuthenticated) {
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            exit;
        }
    }

    /**
     * Because you really should! Speed increase by a 2x magnitude
     */
    protected function warnIfWrongCacheIsUsed()
    {
        if ($this->getCache() instanceof Symfony\Component\Cache\Simple\FilesystemCache) {
            $this->getLogger()->info("OPCode cache is not enabled. To get maximum performance, enable it in php.ini");
        }
    }

    /**
     * Add AlertifyJS requirements
     *
     * @link http://alertifyjs.com
     */
    protected function requireAlertifyJS()
    {
        Requirements::javascript('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.0/alertify.min.js');
        $dir = i18n::get_script_direction();
        if ($dir == 'rtl') {
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.0/css/alertify.rtl.min.css');
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.0/css/themes/default.rtl.min.css');
        } else {
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.0/css/alertify.min.css');
            Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/AlertifyJS/1.11.0/css/themes/default.min.css');
        }
    }

    /**
     * Display the flash message if any using Alertifyjs
     *
     * @link http://alertifyjs.com/
     * @return void
     */
    protected function displayFlashMessage()
    {
        try {
            $FlashMessage = $this->getSession()->get('FlashMessage');
        } catch (Exception $ex) {
            $FlashMessage = null; // Session can be null (eg : Security)
        }
        if (!$FlashMessage) {
            return;
        }
        $this->getSession()->clear('FlashMessage');
        $this->requireAlertifyJS();
        $msg = addslashes($FlashMessage['Message']);
        $type = $FlashMessage['Type'];
        switch ($type) {
            case 'good':
                $type = 'success';
                break;
            case 'bad':
                $type = 'error';
                break;
        }
        $js = "alertify.notify('$msg', '$type', 0);";
        Requirements::customScript($js);
    }

    /**
     * Set a message to the session, for display next time a page is shown.
     *
     * @param string $message the text of the message
     * @param string $type Should be set to good, bad, or warning.
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     */
    public function sessionMessage($message, $type = ValidationResult::TYPE_ERROR, $cast = ValidationResult::CAST_TEXT)
    {
        $this->getSession()->set('FlashMessage', [
            'Message' => $message,
            'Type' => $type,
            'Cast' => $cast,
        ]);
    }

    /**
     * Get the session for this app
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/cookies_and_sessions/sessions/
     * @return SilverStripe\Control\Session
     */
    public function getSession()
    {
        return $this->getRequest()->getSession();
    }

    /**
     * Get the cache for this app
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/performance/caching/
     * @return Psr\SimpleCache\CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get logger
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/debugging/error_handling/
     * @return  Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
