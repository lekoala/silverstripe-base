<?php
namespace LeKoala\Base\Forms\GridField;

use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_ActionProvider;

/**
 * Provide a simple way to declare buttons that affects a whole GridField
 */
abstract class GridFieldTableButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{

    /**
     * Fragment to write the button to
     * @string
     */
    protected $targetFragment;

    /**
     * @var boolean
     */
    protected $noAjax = true;

    /**
     * @var string
     */
    protected $buttonLabel;

    /**
     * @var string
     */
    protected $fontIcon;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     * @param string $buttonLabel
     */
    public function __construct($targetFragment = "before", $buttonLabel = null)
    {
        $this->targetFragment = $targetFragment;
        if ($buttonLabel) {
            $this->buttonLabel = $buttonLabel;
        }
    }

    public function getActionName()
    {
        $class = ClassHelper::getClassWithoutNamespace(get_called_class());
        // ! without lowercase, in does not work
        return strtolower(str_replace('Button', '', $class));
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $action = $this->getActionName();

        $button = new GridField_FormAction(
            $gridField,
            $action,
            $this->buttonLabel,
            $action,
            null
        );
        $button->addExtraClass('btn btn-secondary action_' . $action);
        if ($this->noAjax) {
            $button->addExtraClass('no-ajax');
        }
        if ($this->fontIcon) {
            $button->addExtraClass('font-icon-' . $this->fontIcon);
        }
        $button->setForm($gridField->getForm());
        return array(
            $this->targetFragment => $button->Field()
        );
    }

    public function getActions($gridField)
    {
        return array($this->getActionName());
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (in_array($actionName, $this->getActions($gridField))) {
            return $this->handle($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array(
            $this->getActionName() => 'handle',
        );
    }

    abstract public function handle($gridField, $request = null);

    /**
     * Get the value of fontIcon
     *
     * @return  string
     */
    public function getFontIcon()
    {
        return $this->fontIcon;
    }

    /**
     * Set the value of fontIcon
     *
     * @param  string  $fontIcon
     *
     * @return  self
     */
    public function setFontIcon($fontIcon)
    {
        $this->fontIcon = $fontIcon;

        return $this;
    }
}
