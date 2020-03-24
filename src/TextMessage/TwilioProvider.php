<?php

namespace LeKoala\Base\TextMessage;

use Exception;
use Twilio\Rest\Client;
use SilverStripe\Security\Member;
use SilverStripe\Core\Environment;

/**
 * A twilio text message provider
 *
 * SilverStripe\Core\Injector\Injector:
 *   LeKoala\Base\TextMessage\ProviderInterface:
 *     class: 'LeKoala\Base\TextMessage\TwilioProvider'
 */
class TwilioProvider implements ProviderInterface
{
    public static function getAccountSid()
    {
        return Environment::getEnv('TWILIO_ACCOUNT_SID');
    }

    public static function getAuthToken()
    {
        return Environment::getEnv('TWILIO_AUTH_TOKEN');
    }

    public static function getPhoneNumber()
    {
        return Environment::getEnv('TWILIO_PHONE_NUMBER');
    }

    public static function getSendAllTo()
    {
        return Environment::getEnv('TWILIO_SEND_ALL_TO');
    }

    /**
     * Send a sms with twilio api
     *
     * @param Member $member
     * @param string $body
     * @return string|boolean Results
     */
    public function send(Member $member, $body)
    {
        if (!$member->Mobile) {
            throw new Exception("Member does not have a mobile number");
        }
        if (!self::getAccountSid()) {
            throw new Exception("Missing account sid");
        }

        // Filter number
        $to = $member->Mobile;
        $to = str_replace([' ', '-', '/'], '', $to);

        if (strlen($to) < 8) {
            throw new Exception("Phone number $to is too sort");
        }

        // Add country prefix if needed
        if (strpos($to, '+') !== 0 && $member->CountryCode) {
            $prefix = $member->dbObject('Country')->getCountryPhonePrefix();
            if ($prefix) {
                $to = ltrim($to, '0');
                $to = '+' . $prefix . $to;
            }
        }

        $account_sid = self::getAccountSid();
        $auth_token = self::getAuthToken();

        $client = new Client($account_sid, $auth_token);

        if (self::getSendAllTo()) {
            $to = self::getSendAllTo();
        }

        $from = self::getPhoneNumber();
        if (!$from) {
            throw new Exception("No from phone number defined in env");
        }

        $response = $client->messages->create(
            $to,
            [
                'from' => $from,
                'body' => $body
            ]
        );

        return $response;
    }
}
