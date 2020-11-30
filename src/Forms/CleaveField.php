<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;
use LeKoala\Base\View\CommonRequirements;

/**
 * Format input using cleave.js
 *
 * @link https://nosir.github.io/cleave.js/
 */
class CleaveField extends TextField
{
    use ConfigurableField;

    public function Type()
    {
        return 'cleave';
    }

    public function extraClass()
    {
        return 'text ' . parent::extraClass();
    }

    public function Field($properties = array())
    {
        $this->setAttribute('data-module', 'Cleave');
        $this->setAttribute('data-config', json_encode($this->config));
        self::requirements();
        return parent::Field($properties);
    }

    public static function requirements()
    {
        CommonRequirements::cleave();

        // Order matters for hooks ! Otherwise ready may fire before hooks are defined!
        Requirements::javascript('base/javascript/ModularBehaviour.js');
    }
}
