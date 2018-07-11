<?php
namespace LeKoala\Base\Actions;

use SilverStripe\Forms\FormAction;

/**
 * Custom actions to use in getCMSActions
 */
class CustomAction extends FormAction
{
    use CustomButton;

    public $useButtonTag = true;

    public function __construct($name, $title, $form = null)
    {
        // Actually, an array works just fine!
        $name = 'doCustomAction[' . $name . ']';

        parent::__construct($name, $title, $form);
    }

    public function Type()
    {
        return 'custom-action';
    }

    public function Field($properties = array())
    {
        if ($this->buttonIcon) {
            $this->buttonContent = $this->getButtonTitle();
        }
        return parent::Field($properties);
    }
}
