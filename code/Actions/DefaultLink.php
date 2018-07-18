<?php
namespace LeKoala\Base\Actions;

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
     * Actions are forwared to the Model if possible
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
            $allParams = $ctrl->getRequest()->allParams();
            $params = array_merge(['CustomLink' => $action], $params);
            $modelClass = $fieldName = $ctrl->getRequest()->param('ModelClass');
            // $action = $modelClass . '/' . $action;
            // Full link to allow one central point to catch all requests and forward them to the model
            $action = $modelClass . '/EditForm/field/' . $fieldName . '/item/' . $allParams['ID'] . '/doCustomLink';
        }
        if (!empty($params)) {
            $action .= '?' . http_build_query($params);
        }
        return $ctrl->Link($action);
    }
}
