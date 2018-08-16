<?php
namespace LeKoala\Base\Controllers;

use \Exception;
use SilverStripe\i18n\i18n;
use LeKoala\Base\View\Alertify;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use LeKoala\Base\View\DeferBackend;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use LeKoala\Base\View\CookieConsent;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Session;

/**
 * A more opiniated base controller for your app
 *
 * Most group of functions are grouped within traits when possible
 *
 * @mixin \LeKoala\Base\Theme\ThemeControllerExtension
 */
class BaseContentController extends ContentController
{
    use SubsiteController;
    use ImprovedActions;
    use WithJsonResponse;
    use Messaging;
    use PageGetters;
    use TimeHelpers;

    /**
     * Inject public dependencies into the controller
     *
     * @var array
     */
    private static $dependencies = [
        'logger' => '%$Psr\Log\LoggerInterface',
        'cache' => '%$Psr\SimpleCache\CacheInterface.app', // see _config/cache.yml,
        'environmentChecker' => '%$LeKoala\Base\Dev\EnvironmentChecker',
    ];
    /**
     * @var Psr\Log\LoggerInterface
     */
    public $logger;
    /**
     * @var Psr\SimpleCache\CacheInterface
     */
    public $cache;
    /**
     * @var LeKoala\Base\Dev\EnvironmentChecker
     */
    public $environmentChecker;

    protected function init()
    {
        // Ensure you load with "defer" your libs!
        // @link https://flaviocopes.com/javascript-async-defer/#tldr-tell-me-whats-the-best
        Requirements::set_backend(new DeferBackend);

        try {
            parent::init();
        } catch (DatabaseException $ex) {
            $dbMessage = $ex->getMessage();
            // If we have a missing column, we can try to rebuild
            // TODO: add some kind of checks to prevent hammering
            if (strpos($dbMessage, 'Unknown column') !== false) {
                $dbAdmin = new DatabaseAdmin();
                $dbAdmin->doBuild(true);
            }
            // Rethrow the exception
            throw $ex;
        }

        // Without this, objects cannot decide for themselves if they want hash rewrite
        // @link https://github.com/silverstripe/silverstripe-framework/issues/7447
        // @link https://docs.silverstripe.org/en/4/developer_guides/templates/how_tos/disable_anchor_links/
        SSViewer::setRewriteHashLinksDefault(false);

        // Force SSL from client config
        // You should really do this with your webserver config instead
        $SiteConfig = $this->SiteConfig();
        if ($SiteConfig->ForceSSL) {
            Director::forceSSL();
        }

        // Third party scripts (google analytics, etc)
        $SiteConfig->requireGoogleAnalytics();
        if (CookieConsent::IsEnabled()) {
            CookieConsent::requirements();
        }

        $this->setLangFromRequest();

        $this->environmentChecker->check($this);

        try {
            Alertify::checkFlashMessage($this->getSession());
        } catch (Exception $ex) {
            // There might not be a session
        }

        // Always helpful!
        if (Director::isDev() && !Director::is_ajax()) {
            SSViewer::config()->source_file_comments = true;
        }

        // Switch channel for clearer logs
        $this->logger = $this->logger->withName('app');
    }

    /**
     * Does current request expects json?
     * @return boolean
     */
    public function isJson()
    {
        if (Director::is_ajax() && in_array('application/json', $this->getRequest()->getAcceptMimetypes(false))) {
            return true;
        }
        return false;
    }

    /**
     * @return Controller
     */
    public static function safeCurr()
    {
        if (static::has_curr()) {
            return static::curr();
        }
        return null;
    }

    /**
     * @param string $segment
     * @return string
     */
    public function IsCurrentSegment($segment)
    {
        return $segment == $this->URLSegment ? 'current' : 'link';
    }

    /**
     * @param string $segment
     * @return string
     */
    public function IsCurrentAction($action)
    {
        return $action == $this->action ? 'current' : 'link';
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
     * Get the session for this app
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/cookies_and_sessions/sessions/
     * @return SilverStripe\Control\Session
     */
    public function getSession()
    {
        try {
            $session = $this->getRequest()->getSession();
        } catch (Exception $ex) {
            $session = new Session($_SESSION);
        }
        return $session;
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
