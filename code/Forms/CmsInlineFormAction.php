<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use LeKoala\Base\Actions\DefaultLink;

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
    use DefaultLink;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var boolean
     */
    protected $newWindow = false;

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
        if (!$this->url) {
            return $this->getControllerLink($this->action, $this->params);
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
        $attrs = '';
        if ($this->newWindow) {
            $attrs .= ' target="_blank"';
        }
        $content = '<a href="' . $link . '" class="btn btn-primary action inline-action no-ajax"' . $attrs . '>';
        $content .= $this->content;
        $content .= '</a>';
        $this->content = $content;

        return parent::FieldHolder($properties);
    }

    /**
     * Get the value of params
     *
     * @return  array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set the value of params
     *
     * @param  array  $params
     *
     * @return  self
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get the value of newWindow
     *
     * @return  boolean
     */
    public function getNewWindow()
    {
        return $this->newWindow;
    }

    /**
     * Set the value of newWindow
     *
     * @param  boolean  $newWindow
     *
     * @return  self
     */
    public function setNewWindow($newWindow)
    {
        $this->newWindow = $newWindow;

        return $this;
    }
}
