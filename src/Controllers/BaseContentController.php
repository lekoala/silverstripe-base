<?php

namespace LeKoala\Base\Controllers;

use \Exception;
use LeKoala\Base\Helpers\JsonHelper;
use LeKoala\Base\Helpers\PathHelper;
use SilverStripe\i18n\i18n;
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
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use LeKoala\Base\Helpers\ThemeHelper;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\DeferBackend\CspProvider;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\HTTPRequest;
use LeKoala\DeferBackend\DeferBackend;
use LeKoala\Multilingual\LangHelper;
use MySiteConfigExtension;
use Random\Engine\Secure;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;

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
     * @var array<string>
     */
    private static $dependencies = [
        'logger' => '%$Psr\Log\LoggerInterface',
        'cache' => '%$Psr\SimpleCache\CacheInterface.app', // see _config/cache.yml,
    ];
    /**
     * @config
     * @var string|null
     */
    private static $default_referrer_policy;
    /**
     * @config
     * @var bool
     */
    private static $enable_hsts = false;
    /**
     * @config
     * @var bool
     */
    private static $enable_csp = false;
    /**
     * Never null due to dependencies
     * @var \Monolog\Logger
     */
    public $logger;
    /**
     * Never null due to dependencies
     * @var CacheInterface
     */
    public $cache;

    /**
     * @return void
     */
    protected function init()
    {
        // Guard resources
        if (ThemeHelper::isAdminTheme()) {
            parent::init();
            return;
        }

        if (Director::isDev()) {
            // This should probably happen at middlewazre level
            // $FORCE_SUBSITE = Environment::getEnv('FORCE_SUBSITE');
            // if ($FORCE_SUBSITE) {
            //     SubsiteHelper::changeSubsite($FORCE_SUBSITE, true);
            //     $this->dataRecord->SubsiteID = $FORCE_SUBSITE;
            // }
        }

        DeferBackend::replaceBackend();

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
        LangHelper::persistLocaleIfCookiesAreAllowed();

        $this->setLangFromRequest();

        try {
            FlashMessage::checkFlashMessage($this->getSession());
        } catch (Exception $ex) {
            // There might not be a session
        }

        // Switch channel for clearer logs
        $this->logger = $this->logger->withName('app');

        if ($SiteConfig->LogoID) {
            $LogoSchemaMarkup = $this->getCache()->get('LogoSchemaMarkup');
            if (!$LogoSchemaMarkup) {
                $LogoSchemaMarkup = $this->LogoSchemaMarkup();
                $this->getCache()->set('LogoSchemaMarkup', $LogoSchemaMarkup, 3600);
            }
            Requirements::insertHeadTags('<script type="application/ld+json">' . $LogoSchemaMarkup . '</script>', 'LogoSchemaMarkup');
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
        $sc = MySiteConfigExtension::currSiteConfig();
        $logoLink = PathHelper::absoluteURL($sc->Logo()->Link());

        $arr = [];
        $arr['@context'] = "https://schema.org";
        $arr['@type'] = "Organization";
        $arr['url'] = Director::absoluteBaseURL();
        $arr['logo'] = $logoLink;
        $arr['name'] = $sc->getTitle();

        return JsonHelper::encode($arr);
    }

    public function handleRequest(HTTPRequest $request): HTTPResponse
    {
        $response = parent::handleRequest($request);

        CspProvider::addSecurityHeaders($response);
        CspProvider::addCspHeaders($response);

        return $response;
    }

    /**
     * @return Controller|null
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
        //@phpstan-ignore-next-line
        return $segment == $this->URLSegment ? 'current' : 'link';
    }

    /**
     * @param string $action
     * @return string
     */
    public function IsCurrentAction($action)
    {
        //@phpstan-ignore-next-line
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
        $member = Security::getCurrentUser();
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
        $lang = $request->getVar('lang');
        if ($lang) {
            if (strlen($lang) == 2) {
                $lang = LangHelper::get_locale_from_lang($lang);
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
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        //@phpstan-ignore-next-line
        return $this->logger;
    }
}
