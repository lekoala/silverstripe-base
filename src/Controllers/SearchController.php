<?php

namespace LeKoala\Base\Controllers;

use PageController;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\ORM\FieldType\DBField;
use LeKoala\Base\Extensions\URLSegmentExtension;

/**
 * Class \LeKoala\Base\Controllers\SearchController
 */
class SearchController extends PageController
{
    /**
     * Process and render search results.
     */
    public function index(HTTPRequest $request = null)
    {
        $request = $this->getRequest();
        $Query = $request->getVar('q');
        $SearchList = new ArrayList();
        if ($Query) {
            $FullQuery = str_replace(' ', '%', $Query);
            $excludedClasses = [
                ErrorPage::class,
            ];
            $filters =  [
                "Title:PartialMatch" => $FullQuery,
                "Content:PartialMatch" => $FullQuery,
            ];
            $Results = SiteTree::get()->filterAny($filters)->exclude('ClassName', $excludedClasses);
            foreach ($Results as $Result) {
                if ($Result->canView()) {
                    $SearchList->push($Result);
                }
            }
            // $SearchList->merge($Results);

            // also search dataobjects with an url segment
            $dataObjects = ClassHelper::extendedBy(URLSegmentExtension::class);
            foreach ($dataObjects as $dataObject) {
                $sng = singleton($dataObject);
                if ($sng->hasMethod('isSearchable')) {
                    if (!$sng->isSearchable()) {
                        continue;
                    }
                }

                $filters = [];
                if ($sng->hasMethod('getSearchFilters')) {
                    $filters = $sng->getSearchFilters();
                } else {
                    $fields = Config::inst()->get($dataObject, 'db');
                    if (isset($fields['Title'])) {
                        $filters['Title:PartialMatch'] = $FullQuery;
                    }
                    if (isset($fields['Name'])) {
                        $filters['Name:PartialMatch'] = $FullQuery;
                    }
                    if (isset($fields['Content'])) {
                        $filters['Content:PartialMatch'] = $FullQuery;
                    }
                    if (isset($fields['Description'])) {
                        $filters['Description:PartialMatch'] = $FullQuery;
                    }
                }

                $Results = $dataObject::get()->filterAny($filters);
                if ($Results) {
                    foreach ($Results as $Result) {
                        if ($Result->canView()) {
                            $SearchList->push($Result);
                        }
                    }
                    // $SearchList->merge($Results);
                }
            }
        }

        $PaginatedList = new PaginatedList($SearchList, $request);
        $data = array(
            'Results' => $PaginatedList,
            'Query' => DBField::create_field('Text', $Query),
            'Title' => _t('SimpleSearchControllerExtension.SearchResults', 'Search Results'),
            'YouSearchedFor' => _t('SimpleSearchControllerExtension.YouSearchFor', 'You searched for %s', [$Query]),
        );
        return $this->customise($data)->renderWith(array('Page_results', 'Page'));
    }
}
