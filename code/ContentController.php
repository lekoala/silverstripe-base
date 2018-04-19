<?php
namespace LeKoala\Base;

use \Exception;
use SilverStripe\i18n\i18n;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\CMS\Controllers\ContentController as DefaultController;
use SilverStripe\Control\Cookie;
use LeKoala\Base\View\Alertify;
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
        // Ensure you load with "defer" your libs!
        // @link https://flaviocopes.com/javascript-async-defer/#tldr-tell-me-whats-the-best
        Requirements::backend()->setWriteJavascriptToBody(false);

        if (Director::isTest()) {
            $this->requireHttpBasicAuth();
        }
        parent::init();

        $this->setLangFromRequest();
        $this->warnIfWrongCacheIsUsed();

        // A few helpful things in dev mode
        if (Director::isDev()) {
            $this->ensureTempFolderExists();
            $this->allowAutologin();
        }

        $this->displayFlashMessage();

        // Switch channel for clearer logs
        $this->logger = $this->logger->withName('app');
    }

    /**
     * Controller's default action handler.  It will call the method named in "$Action", if that method
     * exists. If "$Action" isn't given, it will use "index" as a default.
     *
     * @param HTTPRequest $request
     * @param string $action
     *
     * @return DBHTMLText|HTTPResponse
     */
    protected function handleAction($request, $action)
    {
        // try {
            $result = parent::handleAction($request, $action);
        // } catch (ValidationEx $ex) {
        //     d($ex);
        // }
        return $result;
    }

    /**
     * The class to be applied on your body tag
     *
     * Called <body class="$BodyClass"> in your templates
     *
     * @return string
     */
    public function BodyClass()
    {
        /* @var $page Page */
        $page = $this->data();

        // Append class name
        $parts = explode('\\', get_class($page));
        $class = end($parts);

        // Append action
        $class .= ' ' . ucfirst($this->action) . 'Action';

        // Allow custom extension point
        if ($page->hasMethod('updateBodyClass')) {
            $page->updateBodyClass($class);
        }

        // On Security (which extends a default controller), add Security
        if ($page->URLSegment == 'Security') {
            $class .= ' Security';
        }
        return $class;
    }

    /**
     *  Allow lang to be set by the request. This must happen after parent::init()
     *
     * @return void
     */
    protected function setLangFromRequest()
    {
        $lang = $this->getRequest()->getVar('lang');
        if ($lang) {
            if (strlen($lang) == 2) {
                $lang = i18n::get_closest_translation($lang);
            }
            Cookie::set('ChosenLocale', $lang);
            i18n::set_locale($lang);
        }
        $chosenLocale = Cookie::get('ChosenLocale');
        if ($chosenLocale && preg_match('/^[a-z]{2}_[A-Z]{2}$/', $chosenLocale)) {
            i18n::set_locale($chosenLocale);
        }
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
     *
     * @return void
     */
    protected function warnIfWrongCacheIsUsed()
    {
        if ($this->getCache() instanceof Symfony\Component\Cache\Simple\FilesystemCache) {
            $this->getLogger()->info("OPCode cache is not enabled. To get maximum performance, enable it in php.ini");
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

    /**
     * Easily login on dev sites
     * Do not run this on production
     *
     * @return void
     */
    protected function allowAutologin()
    {
        $request = $this->getRequest();
        if ($request->getVar('autologin')) {
            $admin = Security::findAnAdministrator();
            // $admin->login() is deprecated
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($admin, true, $request);
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
        Alertify::requirements();
        Alertify::show($FlashMessage['Message'], $FlashMessage['Type']);
    }

    /**
     * Set a message to the session, for display next time a page is shown.
     *
     * @param string $message the text of the message
     * @param string $type Should be set to good, bad, or warning.
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     */
    public function sessionMessage($message, $type = ValidationResult::TYPE_spERROR, $cast = ValidationResult::CAST_TEXT)
    {
        $this->getSession()->set('FlashMessage', [
            'Message' => $message,
            'Type' => $type,
            'Cast' => $cast,
        ]);
    }

    /**
     * @param string $message
     * @return void
     */
    public function success($message)
    {
        $this->sessionMessage($message, 'good');
    }

    /**
     * @param string $message
     * @return void
     */
    public function error($message)
    {
        $this->sessionMessage($message, 'bad');
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
