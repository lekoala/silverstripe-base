<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use LeKoala\Base\Actions\DefaultLink;

/**
 * A simple pure css modal for usage in the cms
 *
 * If your content contains a form, it should be loaded through an iframe
 * because you cannot nest forms
 *
 * @author lekoala
 */
class CmsInlineModal extends LiteralField
{
    use DefaultLink;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $iframe;

    public function __construct($name, $title, $content)
    {
        parent::__construct($name, $content);
        $this->title = $title;
    }

    public function FieldHolder($properties = array())
    {
        Requirements::css('base/css/pure-modal.css');

        $attrs = '';
        $modalID = 'modal_' . $this->name;

        $content = '<label for="' . $modalID . '" class="btn btn-primary"' . $attrs . '>';
        $content .= $this->title;
        $content .= '</label>';
        $content .= '<div class="pure-modal from-top">';
        // This is how we show the modal
        $content .= '<input id="' . $modalID . '" class="checkbox" type="checkbox">';
        $content .= '<div class="pure-modal-overlay">';
        // Close in overlay
        $content .= '<label for="' . $modalID . '" class="o-close"></label>';
        $content .= '<div class="pure-modal-wrap">';
        // Close icon
        $content .= '<label for="' . $modalID . '" class="close">&#10006;</label>';
        $content .= $this->content;
        // Iframe if set
        if ($this->iframe) {
            $content .= '<iframe src="' . $this->iframe . '" width="100%%" height="400px" frameBorder="0"></iframe>';
        }
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';
        $this->content = $content;

        return parent::FieldHolder($properties);
    }

    /**
     * Get the value of title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @param string $title
     * @return  self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of iframe
     *
     * @return string
     */
    public function getIframe()
    {
        return $this->iframe;
    }

    /**
     * Set the value of iframe
     *
     * @param string $iframe
     * @return $this
     */
    public function setIframe($iframe)
    {
        $this->iframe = $iframe;

        return $this;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setIframeAction($action)
    {
        return $this->setIframe($this->getControllerLink($action));
    }
}
