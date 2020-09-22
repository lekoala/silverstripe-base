<?php

namespace LeKoala\Base\Controllers;

use ReflectionMethod;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use LeKoala\Base\Helpers\ClassHelper;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\ORM\ValidationException;

/**
 * Improves SilverStripe defaults actions
 *
 * - If a method accepts a HTTPRequest, it does not need to be declared in allowed_actions
 * - adds getRequestedRecord helper (a generic way to fetch a record based on given parameters)
 * - Improve handleAction to catch ValidationException and display them according to context (json response in ajax or alert message)
 */
trait ImprovedActions
{
    /**
     * Returns the instance of the requested record for this request
     * Permissions checks are up to you
     *
     * @return DataObject
     */
    public function getRequestedRecord()
    {
        $request = $this->getRequest();

        // Look first in headers
        $class = $request->getHeader('X-RecordClassName');
        if (!$class) {
            $class = $request->requestVar('_RecordClassName');
        }
        $ID = $request->param('ID');
        if (!$class) {
            // Help our fellow developpers
            if ($ID == 'field') {
                throw new ValidationException("Attempt to post on a FormField often result in loosing request params. No record class could be found");
            }
            throw new ValidationException("No class in request");
        }
        if (!ClassHelper::isValidDataObject($class)) {
            throw new ValidationException("$class is not valid");
        }
        $ID = $request->getHeader('X-RecordID');
        if (!$ID) {
            $ID = (int) $request->requestVar('_RecordID');
        }
        return DataObject::get_by_id($class, $ID);
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
        $action = filter_var($action, FILTER_SANITIZE_STRING);

        // Keep in mind we can only create a reflection of action from the base class
        // and not those provided by extensions, eg: search
        if (method_exists($this->owner, $action)) {
            $refl = new ReflectionMethod($this->owner, $action);
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
        } catch (RedirectionException $ex) {
            return $this->redirect($ex->getRedirectUrl());
        } catch (ValidationException $ex) {
            $caller = $ex->getTrace();
            $callerFile = $caller[0]['file'] ?? 'unknonwn';
            $callerLine = $caller[0]['line'] ?? 0;
            $this->getLogger()->debug($ex->getMessage() . ' in ' . basename($callerFile) . ':' . $callerLine);
            if (Director::is_ajax()) {
                return $this->applicationResponse($ex->getMessage(), [], [
                    'code' => $ex->getCode(),
                ], false);
            } else {
                return $this->error($ex->getMessage());
            }
        }
        return $result;
    }
}
