<?php
namespace LeKoala\Base\Actions;

use SilverStripe\Forms\FormAction;


class CustomAction extends FormAction {

   public function __construct($name, $title, $form = null)
   {
       // Actually, an array works just fine!
       $name = 'doCustomAction[' . $name . ']';

       parent::__construct($name, $title, $form);
   }

}
