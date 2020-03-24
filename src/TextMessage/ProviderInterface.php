<?php

namespace LeKoala\Base\TextMessage;

use SilverStripe\Security\Member;

/**
 * A common interface for sending text messages
 */
interface ProviderInterface
{
    public function send(Member $member, $body);
}
