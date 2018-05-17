<?php
namespace LeKoala\Base;

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
 * @property \SilverStripe\CMS\Controllers\ContentController|\LeKoala\Base\SimpleSearchControllerExtension $owner
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
        $placeholder = _t('SilverStripe\\CMS\\Search\\SearchForm.SEARCH', 'Search');
        $searchText = '';
        if ($this->owner->getRequest() && $this->owner->getRequest()->getVar('Search')) {
            $searchText = $this->owner->getRequest()->getVar('Search');
        }
        $fields = new FieldList(
            $Search = new TextField('q', false, $searchText)
        );
        $Search->setAttribute('placeholder', $placeholder);
        $actions = new FieldList(
            $Go = new FormAction('doSearch', _t('SilverStripe\\CMS\\Search\\SearchForm.GO', 'Go'))
        );
        $Go->setName('');
        $Go->setUseButtonTag(true);
        $Go->setButtonContent('<span class="fa fa-search"></span>');
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
            $Query = \str_replace(' ', '%', $Query);
            $excludedClasses = [
                ErrorPage::class,
            ];
            $Results = SiteTree::get()->where([
                "Title LIKE ?" => ['%' . $Query . '%'],
                "Content LIKE ?" => ['%' . $Query . '%'],
            ])->exclude('ClassName', $excludedClasses);
        }
        $data = array(
            'Results' => $Results,
            'Query' => DBField::create_field('Text', $Query),
            'Title' => _t('SilverStripe\\CMS\\Search\\SearchForm.SearchResults', 'Search Results')
        );
        return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
    }
}
