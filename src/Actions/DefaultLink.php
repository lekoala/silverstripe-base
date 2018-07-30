<?php
namespace LeKoala\Base\Actions;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;

trait DefaultLink
{
    /**
     * Build a url to call an action on current model
     *
     * Takes into account ModelAdmin current model and set some defaults parameters
     * to send along
     *
     * @param string $action
     * @param array $params
     * @return string
     */
    public function getModelLink($action, array $params = null)
    {
        if ($params === null) {
            $params = [];
        }
        $ctrl = Controller::curr();
        if ($ctrl instanceof ModelAdmin) {
            $allParams = $ctrl->getRequest()->allParams();
            $params = array_merge(['CustomLink' => $action], $params);
            $modelClass = $fieldName = $ctrl->getRequest()->param('ModelClass');
            $action = $modelClass . '/EditForm/field/' . $fieldName . '/item/' . $allParams['ID'] . '/doCustomLink';
        }
        if (!empty($params)) {
            $action .= '?' . http_build_query($params);
        }
        return $ctrl->Link($action);
    }

    /**
     * Build an url for the current controller and pass along some parameters
     *
     * @param string $action
     * @param array $params
     * @return string
     */
    public function getControllerLink($action, array $params = null)
    {
        if ($params === null) {
            $params = [];
        }
        $ctrl = Controller::curr();
        if ($ctrl instanceof ModelAdmin) {
            $allParams = $ctrl->getRequest()->allParams();
            $modelClass = $fieldName = $ctrl->getRequest()->param('ModelClass');
            $action = $modelClass . '/' . $action;
            $params = array_merge($allParams, $params);
        }
        if (!empty($params)) {
            $action .= '?' . http_build_query($params);
        }
        return $ctrl->Link($action);
    }
}
