<?php
namespace LeKoala\Base\Controllers;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\ValidationResult;
use LeKoala\Base\Subsite\SubsiteHelper;

trait Messaging
{
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
    public function success($message, $linkOrManipulations = true)
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
    public function error($message, $linkOrManipulations = true)
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
     * @param array|boolean $manipulations see createManipulation
     * @param array|boolean $extraData
     * @param boolean $success you might rather throw ValidationException instead
     * @return HTTPResponse
     */
    protected function applicationResponse($message, $manipulations = [], $extraData = [], $success = true)
    {
        if (is_bool($manipulations)) {
            $success = $manipulations;
            $manipulations = [];
            $extraData = [];
        }
        if (is_bool($extraData)) {
            $success = $extraData;
            $extraData = [];
        }
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
     * @param string $action Action to apply (replaceWith by default)
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
}
