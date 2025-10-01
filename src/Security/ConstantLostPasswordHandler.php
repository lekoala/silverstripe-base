<?php

namespace LeKoala\Base\Security;

use LeKoala\Base\Security\ConstantTimeOperation;
use SilverStripe\Security\MemberAuthenticator\LostPasswordHandler;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\Form;

class ConstantLostPasswordHandler extends LostPasswordHandler
{
    public function forgotPassword(array $data, Form $form): HTTPResponse
    {
        return ConstantTimeOperation::execute(fn() => parent::forgotPassword($data, $form));
    }
}
