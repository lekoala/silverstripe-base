<?php

namespace LeKoala\Base\Controllers;

use \Exception;
use SilverStripe\i18n\i18n;
use Psr\Log\LoggerInterface;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Session;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use LeKoala\Base\View\FlashMessage;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use LeKoala\Base\View\CookieConsent;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use LeKoala\Base\Helpers\ThemeHelper;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Dev\EnvironmentChecker;
use LeKoala\Base\i18n\BaseI18n;
use LeKoala\DeferBackend\CspProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\HTTPRequest;
use LeKoala\DeferBackend\DeferBackend;

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
    use LangHelpers;
    use MenuHelpers;

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
     * @config
     * @var string
     */
    private static $default_referrer_policy;
    /**
     * @config
     * @var string
     */
    private static $enable_hsts;
    /**
     * @config
     * @var string
     */
    private static $enable_csp;
    /**
     * @var LoggerInterface
     */
    public $logger;
    /**
     * @var CacheInterface
     */
    public $cache;
    /**
     * @var EnvironmentChecker
     */
    public $environmentChecker;

    protected function init()
    {
        // Guard resources
        if (ThemeHelper::isAdminTheme()) {
            parent::init();
            return;
        }

        Requirements::set_backend(new DeferBackend);

        // Maybe we could add dynamically the url handler??
        // $traits = class_uses($this);
        // if (isset($traits[IsRecordController::class])) {
        //     Config::inst()->modify()->merge(get_class($this), 'url_handlers', '$ID/$Action');
        // }

        try {
            parent::init();
        } catch (DatabaseException $ex) {
            $dbMessage = $ex->getMessage();
            // If we have a missing column, we can try to rebuild
            // TODO: add some kind of checks to prevent hammering
            if (strpos($dbMessage, 'Unknown column') !== false) {
                $dbAdmin = new DatabaseAdmin();
                $dbAdmin->doBuild(true);
                sleep(5);
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

        // Third party scripts (google analytics, etc)
        $SiteConfig->requireGoogleAnalytics();
        if (CookieConsent::IsEnabled()) {
            CookieConsent::requirements();
        }

        $this->setLangFromRequest();

        $this->environmentChecker->check($this);

        try {
            FlashMessage::checkFlashMessage($this->getSession());
        } catch (Exception $ex) {
            // There might not be a session
        }

        // Always helpful!
        $request = $this->getRequest();
        if (Director::isDev() && !Director::is_ajax() && $request && !$request->isPOST()) {
            SSViewer::config()->set('source_file_comments', true);
        }

        // Switch channel for clearer logs
        $this->logger = $this->logger->withName('app');

        if ($SiteConfig->LogoID) {
            Requirements::insertHeadTags('<script type="application/ld+json">' . $this->LogoSchemaMarkup() . '</script>', 'LogoSchemaMarkup');
        }
    }

    /**
     * @link https://developers.google.com/search/docs/guides/intro-structured-data#markup-formats-and-placement
     * @link https://developers.google.com/search/docs/data-types/logo
     * @link https://schema.org/Organization
     * @return string
     */
    public function LogoSchemaMarkup()
    {
        $page = $this->dataRecord;
        $sc = SiteConfig::current_site_config();
        $logoLink = Director::absoluteURL($sc->Logo()->Link());

        $arr = [];
        $arr['@context'] = "https://schema.org";
        $arr['@type'] = "Organization";
        $arr['url'] = Director::absoluteBaseURL();
        $arr['logo'] = $logoLink;
        $arr['name'] = $sc->getTitle();

        return json_encode($arr);
    }

    public function handleRequest(HTTPRequest $request)
    {
        $response = parent::handleRequest($request);

        CspProvider::addSecurityHeaders($response);
        CspProvider::addCspHeaders($response);

        return $response;
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
        if ($this->action) {
            $class .= ' ' . ucfirst($this->action) . 'Action';
        }

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
     * A better logout url that supports masquerading
     *
     * @return string
     */
    public function LogoutURL()
    {
        $member = Member::currentUser();
        if ($member && $member->IsMasquerading()) {
            return '/Security/end_masquerade';
        }
        return '/Security/logout';
    }


    /**
     * Allow lang to be set by the request. This must happen after parent::init()
     *
     * @return void
     */
    protected function setLangFromRequest()
    {
        $request = $this->getRequest();
        if (!$request) {
            return;
        }
        $lang = $request->getVar('lang');
        if ($lang) {
            if (strlen($lang) == 2) {
                $lang = BaseI18n::get_locale_from_lang($lang);
            }
            Cookie::set('ChosenLocale', $lang);
        }
        $chosenLocale = Cookie::get('ChosenLocale');
        // Only set if locale has a valid format
        if ($chosenLocale && preg_match('/^[a-z]{2}_[A-Z]{2}$/', $chosenLocale)) {
            i18n::set_locale($chosenLocale);
        }
    }

    /**
     * Get the session for this app
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/cookies_and_sessions/sessions/
     * @return Session
     */
    public function getSession()
    {
        try {
            $session = $this->getRequest()->getSession();
        } catch (Exception $ex) {
            $data = isset($_SESSION) ? $_SESSION : [];
            $session = new Session($data);
        }
        return $session;
    }

    /**
     * Get the cache for this app
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/performance/caching/
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get logger
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/debugging/error_handling/
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
