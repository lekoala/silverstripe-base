<?php

namespace LeKoala\Base\Forms\GridField;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;

/**
 * Provide a simple way to filter gridfields without using slow search ui
 * Filtering should be done in ModelAdmin::getList
 */
class GridFieldQuickFilters implements GridField_HTMLProvider
{

    /**
     * Fragment to write the button to
     * @string
     */
    protected $targetFragment;

    protected $filters;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = "before", $filters = [])
    {
        $this->targetFragment = $targetFragment;
        $this->filters = $filters;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        // Use request because it can be sent through GET (regular) or POST (ajax search form)
        $currFilters = [];
        if (isset($_REQUEST["quickfilters"])) {
            $currFilters = $_REQUEST['quickfilters'];
            if (is_string($currFilters)) {
                $currFilters = explode(",", $currFilters);
            }
        }

        $html = '<div class="quickfilters">';
        // nested form do not work we use js in admin.js to trigger the post
        // $html .= '<form method="get" action="">';
        foreach ($this->filters as $filterValue => $filterLabel) {
            $checked = '';
            if (in_array($filterValue, $currFilters)) {
                $checked = ' checked="checked"';
            }
            $it = '<label><input type="checkbox" name="quickfilters[]" value="' . $filterValue .  '"' . $checked . ' /> ' . $filterLabel . '</label>';
            $html .= $it;
        }
        $html .= '<input type="submit" class="quickfilters-action" value="' . _t('GridFieldQuickFiltesrs.DO_FILTER', 'Filter') . '" />';
        // $html .= '</form>';
        $html .= '</div>';

        return array(
            $this->targetFragment => $html
        );
    }
}
