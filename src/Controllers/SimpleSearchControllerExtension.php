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
        $Go->setButtonContent('<span class="fa fa-search"></span>');
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
