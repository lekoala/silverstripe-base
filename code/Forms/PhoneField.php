<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\TextField;

/**
 *
 */
class PhoneField extends TextField
{
    public function getInputType()
    {
        return 'phone';
    }

    public function Type()
    {
        return 'text';
    }
}
