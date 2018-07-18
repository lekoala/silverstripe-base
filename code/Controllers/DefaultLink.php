<?php
namespace LeKoala\Base\Controllers;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;

trait DefaultLink
{
    /**
     * Build a url on current controller
     *
     * Takes into account ModelAdmin current model and set some defaults parameters
     * to send along
     *
     * @param string $action
     * @param array $params
     * @return string
     */
    public function getDefaultLink($action, array $params = null)
    {
        if ($params === null) {
            $params = [];
        }
        $ctrl = Controller::curr();
        if ($ctrl instanceof ModelAdmin) {
            $modelClass = $ctrl->getRequest()->param('ModelClass');
            $action = $modelClass . '/' . $action;
            $params = array_merge($ctrl->getRequest()->allParams(), $params);
        }
        if (!empty($params)) {
            $action .= '?' . http_build_query($params);
        }
        return $ctrl->Link($action);
    }
}
