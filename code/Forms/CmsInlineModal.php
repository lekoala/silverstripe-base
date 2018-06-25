<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use SilverStripe\View\Requirements;

/**
 * CmsInlineModal
 *
 * A simple pure css modal for usage in the cms
 *
 * @author lekoala
 */
class CmsInlineModal extends LiteralField
{
    /**
     * @var string
     */
    protected $title;

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
        $content .= 'here is the content';
        // $content .= $this->content;
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
}
