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

    /**
     * @var bool
     */
    protected $iframeTop;

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

        $onclick = '';
        if ($this->iframe) {
            $onclick = "resizeIframe(document.getElementById(this.getAttribute('for') + '_iframe'))";
        }
        $content = '<label for="' . $modalID . '" class="btn btn-primary"' . $attrs . ' onclick="' . $onclick . '">';
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
        // Iframe if set
        if ($this->iframe && $this->iframeTop) {
            $content .= '<iframe id="' . $modalID . '_iframe" src="' . $this->iframe . '" width="100%%" style="max-height:400px" frameBorder="0" scrolling="auto"></iframe>';
        }
        $content .= $this->content;
        // Iframe if set
        if ($this->iframe && !$this->iframeTop) {
            $content .= '<iframe id="' . $modalID . '_iframe"  src="' . $this->iframe . '" width="100%%" style="max-height:400px" frameBorder="0" scrolling="auto"></iframe>';
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
     * @return $this
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

    /**
     * Get the value of iframeTop
     * @return bool
     */
    public function getIframeTop()
    {
        return $this->iframeTop;
    }

    /**
     * Set the value of iframeTop
     *
     * @param bool $iframeTop
     * @return $this
     */
    public function setIframeTop($iframeTop)
    {
        $this->iframeTop = $iframeTop;
        return $this;
    }
}
