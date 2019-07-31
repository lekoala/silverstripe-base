<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use LeKoala\Base\Actions\DefaultLink;

/**
 * A simple button that links to a given action or url
 *
 * This is meant to be used inside getCMSFields or getCMSUtils
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
     * @var string
     */
    protected $buttonIcon = null;

    /**
     * Create a new action button.
     * @param action The method to call when the button is clicked
     * @param title The label on the button
     * @param extraClass A CSS class to apply to the button in addition to 'action'
     */
    public function __construct($action, $title = "", $extraClass = 'btn-primary')
    {
        parent::__construct($action, $title);
        $this->addExtraClass($extraClass);
    }

    public function performReadonlyTransformation()
    {
        return $this->castedCopy(self::class);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if (!$this->url) {
            return $this->getControllerLink($this->name, $this->params);
        }
        return $this->url;
    }

    /**
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }


    /**
     * Get an icon for this button
     *
     * @return string
     */
    public function getButtonIcon()
    {
        return $this->buttonIcon;
    }

    /**
     * Set an icon for this button
     *
     * Feel free to use SilverStripeIcons constants
     *
     * @param string $buttonIcon An icon for this button
     * @return $this
     */
    public function setButtonIcon(string $buttonIcon)
    {
        $this->buttonIcon = $buttonIcon;
        return $this;
    }

    public function Type()
    {
        return 'inline-action';
    }

    public function FieldHolder($properties = array())
    {
        $link = $this->getUrl();
        $attrs = '';
        if ($this->newWindow) {
            $attrs .= ' target="_blank"';
        }
        if ($this->readonly) {
            $attrs .= ' style="display:none"';
        }
        $content = '<a href="' . $link . '" class="btn ' . $this->extraClass() . ' action no-ajax"' . $attrs . '>';
        $title = $this->content;
        if ($this->buttonIcon) {
            $title = '<span class="font-icon-' . $this->buttonIcon . '"></span> ' . $title;
        }
        $content .= $title;
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
     * @return $this
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
     * @return $this
     */
    public function setNewWindow($newWindow)
    {
        $this->newWindow = $newWindow;

        return $this;
    }
}
