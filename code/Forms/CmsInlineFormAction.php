<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;

/**
 * CmsInlineFormAction
 *
 * This is not the most robust implementation, but it does the job
 *
 * Action must be implemented on the controller (ModelAdmin for instance)
 * The data passed in the content of the form
 *
 * @author lekoala
 */
class CmsInlineFormAction extends LiteralField
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * Create a new action button.
     * @param action The method to call when the button is clicked
     * @param title The label on the button
     * @param extraClass A CSS class to apply to the button in addition to 'action'
     */
    public function __construct($action, $title = "", $extraClass = '')
    {
        parent::__construct($action, $title, null);
    }
    public function performReadonlyTransformation()
    {
        return $this->castedCopy(self::class);
    }
    public function getUrl()
    {
        // Some sensible defaults if no url is specified
        if (!$this->url) {
            $ctrl = Controller::curr();
            $action = $this->name;
            $params = $this->params;
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
        return $this->url;
    }
    public function setUrl($url)
    {
        $this->url = $url;
    }
    public function FieldHolder($properties = array())
    {
        $link = $this->getUrl();

        $content = '<a href="' . $link . '" class="btn btn-primary action no-ajax">';
        $content .= $this->content;
        $content .= '</a>';
        $this->content = $content;

        return parent::FieldHolder($properties);
    }
}
