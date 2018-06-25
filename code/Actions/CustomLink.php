<?php
namespace LeKoala\Base\Actions;

use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\FormField;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;

class CustomLink extends LiteralField
{
    protected $title;
    protected $link;
    protected $confirmation;
    protected $newWindow = true;

    public function __construct($name, $title = null, $link = null)
    {
        if ($title === null) {
            $title = FormField::name_to_label($name);
        }
        if ($link === null) {
            $link = Controller::curr()->Link($name);
        }
        $this->title = $title;
        $this->link = $link;
        parent::__construct($name, '');
    }

    public function FieldHolder($properties = array())
    {
        $link = $this->link;
        $title = $this->title;
        $classes = "btn btn-default";
        $attrs = '';
        if ($this->newWindow) {
            $attrs .= ' target="_blank"';
        }
        if ($this->confirmation) {
            $attrs .= ' onclick="return confirm("' . Convert::raw2htmlatt($this->confirmation) . '");"';
        }
        $content = '<a href="' . $link . '" class="' . $classes . '"' . $attrs . '>' . $title . '</a>';
        $this->content = $content;
        return parent::FieldHolder();
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
     * @return  self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set the value of link
     *
     * @return  self
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get the value of confirmation
     */
    public function getConfirmation()
    {
        return $this->confirmation;
    }

    /**
     * Set the value of confirmation
     *
     * @return  self
     */
    public function setConfirmation($confirmation)
    {
        $this->confirmation = $confirmation;

        return $this;
    }

    /**
     * Get the value of newWindow
     */
    public function getNewWindow()
    {
        return $this->newWindow;
    }

    /**
     * Set the value of newWindow
     *
     * @return  self
     */
    public function setNewWindow($newWindow)
    {
        $this->newWindow = $newWindow;

        return $this;
    }
}
