<?php
namespace LeKoala\Base;

use \Exception;
use ReflectionMethod;
use SilverStripe\i18n\i18n;
use LeKoala\Base\Dev\BasicAuth;
use LeKoala\Base\View\Alertify;
use SilverStripe\Control\Cookie;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\CMS\Controllers\ContentController;
use LeKoala\Base\View\DeferBackend;
use LeKoala\Base\Subsite\SubsiteHelper;

/**
 * A more opiniated base controller for your app
 *
 */
class BaseContentController extends ContentController
{
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
            if (strpos($dbMessage, 'Unknown column') !== false) {
                $dbAdmin = new DatabaseAdmin();
                $dbAdmin->doBuild(true);
            }
            throw $ex;
        }

        $this->setLangFromRequest();

        $this->environmentChecker->check($this);

        try {
            Alertify::checkFlashMessage($this->getSession());
        } catch (Exception $ex) {
            // There might not be a session
        }

        // Switch channel for clearer logs
        $this->logger = $this->logger->withName('app');
    }

    /**
     * @return int
     */
    public function getSubsiteId()
    {
        return SubsiteHelper::CurrentSubsiteID();
    }

    /**
     * Override default mechanisms for ease of use
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/controllers/access_control/
     * @param string $action
     * @return boolean
     */
    public function checkAccessAction($action)
    {
        // Whitelist early on to avoid running unecessary code
        if ($action == 'index') {
            return true;
        }
        $isAllowed = $this->isActionWithRequest($action);
        if (!$isAllowed) {
            $isAllowed = parent::checkAccessAction($action);
        }
        if (!$isAllowed) {
            $this->getLogger()->info("$action is not allowed");
        }
        return $isAllowed;
    }

    /**
     * Checks if a given action use a request as first parameter
     *
     * For forms, declare HTTPRequest $request = null because $request is not set
     * when called from the template
     *
     * @param string $action
     * @return boolean
     */
    protected function isActionWithRequest($action)
    {
        if ($this->owner->hasMethod($action)) {
            $refl = new ReflectionMethod($this, $action);
            $params = $refl->getParameters();
            // Everything that gets a request as a parameter is a valid action
            if ($params && $params[0]->getName() == 'request') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function hasAction($action)
    {
        $result = parent::hasAction($action);
        if (!$result) {
            $result = $this->isActionWithRequest($action);
        }
        return $result;
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
        try {
            $result = parent::handleAction($request, $action);
        } catch (ValidationException $ex) {
            $this->getLogger()->debug($ex);

            if (Director::is_ajax()) {
                return $this->applicationResponse($ex->getMessage(), [], [
                    'code' => $ex->getCode(),
                ], false);
            } else {
                Alertify::show($ex->getMessage(), 'bad');
                return $this->redirectBack();
            }
        }
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
     * @param string|boolean $link Pass true to redirect back
     * @return HTTPResponse
     */
    public function redirectTo($link)
    {
        if ($link === true || is_array($link)) {
            return $this->redirectBack();
        }
        return $this->redirect($this->Link($link));
    }

    /**
     * @param string $message
     * @param string|array $linkOrManipulations
     * @return HTTPResponse
     */
    public function success($message, $linkOrManipulations = null)
    {
        if (Director::is_ajax()) {
            return $this->applicationResponse($message, $linkOrManipulations, [], true);
        }
        $this->sessionMessage($message, 'good');
        if ($linkOrManipulations) {
            return $this->redirectTo($linkOrManipulations);
        }
    }

    /**
     * @param string $message
     * @param string|array $linkOrManipulations
     * @return HTTPResponse
     */
    public function error($message, $linkOrManipulations = null)
    {
        if (Director::is_ajax()) {
            return $this->applicationResponse($message, $linkOrManipulations, [], false);
        }
        $this->sessionMessage($message, 'bad');
        if ($linkOrManipulations) {
            return $this->redirectTo($linkOrManipulations);
        }
    }

    /**
     * Returns a well formatted json response
     *
     * @param string|array $data
     * @return HTTPResponse
     */
    protected function jsonResponse($data)
    {
        $response = $this->getResponse();
        $response->addHeader('Content-type', 'application/json');
        if (!is_string($data)) {
            $data = json_encode($data, JSON_PRETTY_PRINT);
        }
        $response->setBody($data);
        return $response;
    }

    /**
     * Preformatted json response
     * Best handled by scoped-requests plugin
     *
     * @param string $message
     * @param array $manipulations see createManipulation
     * @param array $extraData
     * @param boolean $success you might rather throw ValidationException instead
     * @return HTTPResponse
     */
    protected function applicationResponse($message, $manipulations = [], $extraData = [], $success = true)
    {
        $data = [
            'message' => $message,
            'success' => $success ? true : false,
            'data' => $extraData,
            'manipulations' => $manipulations,
        ];
        return $this->jsonResponse($data);
    }

    /**
     * Helper function to create manipulations
     *
     * Manipulations are scoped inside the specified data-scope
     *
     * @param string $selector Empty selector applies to the entire scope
     * @param string $html Html content to use for action
     * @param string $action Action to apply
     * @return array
     */
    protected function createManipulation($selector, $html = null, $action = null)
    {
        // we have no action or html, it's simply an action on the whole scope (eg : fadeOut)
        if ($action === null && $html === null) {
            $action = $selector;
            $selector = '';
        }
        // we have no action and some html, set a defaultAction
        if ($action === null && $html) {
            $action = 'replaceWith';
        }
        return [
            'selector' => $selector,
            'html' => $html,
            'action' => $action,
        ];
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
