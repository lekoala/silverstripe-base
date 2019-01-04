<?php
namespace LeKoala\Base\Controllers;

use SilverStripe\Control\HTTPResponse;

trait WithJsonResponse
{
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
}
