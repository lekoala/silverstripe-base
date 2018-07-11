<?php
namespace LeKoala\Base\Actions;

use SilverStripe\Forms\FormAction;

class CustomAction extends FormAction
{
    public function __construct($name, $title, $form = null)
    {
        // Actually, an array works just fine!
        $name = 'doCustomAction[' . $name . ']';

        parent::__construct($name, $title, $form);
    }

    public function Field($properties = array())
    {
        // Add a default look if none set
        $extra = $this->extraClass();
        if (strpos($extra, 'btn-') === false) {
            $this->addExtraClass('btn-info');
        }

        return parent::Field($properties);
    }
}
