<?php
namespace LeKoala\Base\Actions;

use Exception;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormField;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\FormAction;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use LeKoala\Base\Controllers\DefaultLink;

/**
 * Custom links to include in getCMSActions
 */
class CustomLink extends LiteralField
{
    use CustomButton;
    use DefaultLink;

    /**
     * @var link
     */
    protected $link;

    /**
     * @var string
     */
    protected $confirmation;

    /**
     * @var boolean
     */
    protected $newWindow = true;

    /**
     * @param string $name
     * @param string $title
     * @param string|array $link Will default to name of link on current controller if not set
     */
    public function __construct($name, $title = null, $link = null)
    {
        if ($title === null) {
            $title = FormField::name_to_label($name);
        }

        parent::__construct($name, '');

        // Reset the title later on because we passed '' to parent
        $this->title = $title;

         // Link (use $this->name, called after parent construct)
        if ($link && is_string($link)) {
            $this->link = $link;
        } else {
            $this->link = $this->getDefaultLink($name, $link);
        }
    }

    public function Type()
    {
        return 'custom-link';
    }

    public function FieldHolder($properties = array())
    {
        $link = $this->link;

        $title = $this->getButtonTitle();
        $classes = $this->extraClass();
        $attrs = '';
        if ($this->newWindow) {
            $attrs .= ' target="_blank"';
        }
        if ($this->confirmation) {
            $attrs .= ' data-confirm="' . Convert::raw2htmlatt($this->confirmation) . '"';
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
