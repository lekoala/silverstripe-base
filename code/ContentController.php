<?php
namespace LeKoala\Base;

use \Exception;
use SilverStripe\View\Requirements;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\CMS\Controllers\ContentController as DefaultController;

/**
 * A more opiniated base controller for your app
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
        parent::init();

        $this->warnIfWrongCacheIsUsed();
        $this->displayFlashMessage();
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

    protected function requireAlertifyJS()
    {
        Requirements::javascript('base/javascript/alertify/alertify.min.js');
        Requirements::css('base/javascript/alertify/css/alertify.min.css');
        Requirements::css('base/javascript/alertify/css/themes/default.min.css');
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
