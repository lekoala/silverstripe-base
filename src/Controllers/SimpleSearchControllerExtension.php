<?php

namespace LeKoala\Base\Controllers;

use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\ORM\FieldType\DBField;

/**
 * A simple alternative to full text search
 *
 * @property \PageController|\LeKoala\Base\Controllers\SimpleSearchControllerExtension $owner
 */
class SimpleSearchControllerExtension extends Extension
{
    private static $allowed_actions = [
        'SimpleSearchForm',
    ];

    /**
     * Simple site search form
     *
     * @return Form
     */
    public function SimpleSearchForm()
    {
        $placeholder = _t('SimpleSearchControllerExtension.SEARCH', 'Search');
        $searchText = '';
        $request = $this->owner->getRequest();
        if ($request) {
            $searchText = $request->getVar('q');
        }
        $fieldsList = [];

        $Search = new TextField('q', false, $searchText);
        $Search->setAttribute('placeholder', $placeholder);
        $fieldsList[] = $Search;

        $fields = new FieldList($fieldsList);

        $actionsList = [];

        $Go = new FormAction('doSearch', _t('SimpleSearchControllerExtension.GO', 'Go'));
        $Go->setName('');
        $Go->setUseButtonTag(true);
        $Go->setButtonContent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M10,18c1.846,0,3.543-0.635,4.897-1.688l4.396,4.396l1.414-1.414l-4.396-4.396C17.365,13.543,18,11.846,18,10 c0-4.411-3.589-8-8-8s-8,3.589-8,8S5.589,18,10,18z M10,4c3.309,0,6,2.691,6,6s-2.691,6-6,6s-6-2.691-6-6S6.691,4,10,4z"/></svg>');
        $actionsList[] =  $Go;

        $actions = new FieldList($actionsList);

        $directorRules = Config::inst()->get(Director::class, 'rules');
        $searchControllerLink = '/sitesearch';
        foreach ($directorRules as $segment => $controller) {
            if ($controller == SearchController::class) {
                $searchControllerLink = '/' . $segment;
            }
        }
        $form = Form::create($this->owner, __FUNCTION__, $fields, $actions);
        $form->setFormMethod('GET');
        $form->setFormAction($searchControllerLink);
        $form->disableSecurityToken();

        return $form;
    }
}
