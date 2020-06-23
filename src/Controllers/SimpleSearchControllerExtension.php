<?php

namespace LeKoala\Base\Controllers;

use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\ORM\FieldType\DBField;

/**
 * A simple alternative to full text search
 *
 * @property \AboutPageController|\AvailableSpacesPageController|\HomePageController|\PageController|\VisionPageController|\PortfolioPageController|\LeKoala\Base\Blocks\BlocksPageController|\LeKoala\Base\Contact\ContactPageController|\LeKoala\Base\Controllers\BaseContentController|\LeKoala\Base\Controllers\RecordController|\LeKoala\Base\Dev\TypographyController|\LeKoala\Base\Faq\FaqPageController|\LeKoala\Base\News\NewsPageController|\LeKoala\Base\Privacy\CookiesRequiredPageController|\SilverStripe\ErrorPage\ErrorPageController|\SilverStripe\CMS\Controllers\ContentController|\SilverStripe\CMS\Model\RedirectorPageController|\LeKoala\Base\Controllers\SimpleSearchControllerExtension $owner
 */
class SimpleSearchControllerExtension extends Extension
{
    private static $allowed_actions = [
        'SimpleSearchForm',
        'search'
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
        if ($this->owner->getRequest() && $this->owner->getRequest()->getVar('Search')) {
            $searchText = $this->owner->getRequest()->getVar('Search');
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

        $form = Form::create($this->owner, __FUNCTION__, $fields, $actions);
        $form->setFormMethod('GET');
        $form->setFormAction($this->owner->Link('search'));
        $form->disableSecurityToken();

        return $form;
    }
    /**
     * Process and render search results.
     */
    public function search()
    {
        $Query = $this->owner->getRequest()->getVar('q');
        $Results = null;
        if ($Query) {
            $FullQuery = \str_replace(' ', '%', $Query);
            $excludedClasses = [
                ErrorPage::class,
            ];
            $Results = SiteTree::get()->filterAny([
                "Title:PartialMatch" => $FullQuery,
                "Content:PartialMatch" => $FullQuery,
            ])->exclude('ClassName', $excludedClasses);
        }
        $data = array(
            'Results' => $Results,
            'Query' => DBField::create_field('Text', $Query),
            'Title' => _t('SimpleSearchControllerExtension.SearchResults', 'Search Results'),
            'YouSearchedFor' => _t('SimpleSearchControllerExtension.YouSearchFor', 'You searched for %s', [$Query]),
        );
        return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
    }
}
