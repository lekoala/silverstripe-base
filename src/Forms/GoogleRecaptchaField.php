<?php
namespace LeKoala\Base\Forms;

use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ValidationException;

/**
 * A simple google recaptcha v2 checkbox integration for your forms
 * Go to https://www.google.com/recaptcha/admin to create an api key
 */
class GoogleRecaptchaField extends LiteralField
{

    /**
     */
    public function __construct()
    {
        try {
            $apiKey = self::getPublicKey();
            $content = '<div class="field g-recaptcha" data-sitekey="' . $apiKey . '"></div>';
        } catch (Exception $ex) {
            $content = '<div style="background:red;color:white">' . $ex->getMessage() . '</div>';
        }
        $name = 'GoogleRecaptcha';

        $this->setContent($content);

        parent::__construct($name, $content);
    }

    public function FieldHolder($properties = array())
    {
        Requirements::javascript("https://www.google.com/recaptcha/api.js");
        return parent::FieldHolder($properties);
    }

    public static function isSetupReady()
    {
        try {
            self::getPublicKey();
            self::getSecretKey();
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public static function getPublicKey()
    {
        $key = Environment::getEnv('RECAPTCHA_PUBLIC_KEY');
        if ($key) {
            return $key;
        }
        if (defined('RECAPTCHA_PUBLIC_KEY')) {
            return RECAPTCHA_PUBLIC_KEY;
        }
        throw new Exception('Please add RECAPTCHA_PUBLIC_KEY to your .env file');
    }

    public static function getSecretKey()
    {
        $key = Environment::getEnv('RECAPTCHA_SECRET_KEY');
        if ($key) {
            return $key;
        }
        if (defined('RECAPTCHA_SECRET_KEY')) {
            return RECAPTCHA_SECRET_KEY;
        }
        throw new Exception('Please add RECAPTCHA_SECRET_KEY to your .env file');
    }

    /**
     * @param array $data Content of the $_POST
     * @return void
     * @throws ValidationException Will throw if invalid response
     */
    public static function validateResponse($data)
    {
        if (empty($data['g-recaptcha-response'])) {
            throw new ValidationException("No recaptcha token");
        }
        $token = $data['g-recaptcha-response'];

        $result = self::postContent("https://www.google.com/recaptcha/api/siteverify", [
            'secret' => self::getSecretKey(),
            'response' => $token,
        ]);

        $decoded = json_decode($result, JSON_OBJECT_AS_ARRAY);

        if (!$decoded) {
            throw new ValidationException("JSON error : " . json_last_error_msg());
        }

        $success = $decoded['success'];

        if (!$success) {
            $error = implode("; ", $decoded['error-codes']);
            throw new ValidationException("recaptcha error : " . $error);
        }
    }

    public static function postContent($url, $postdata)
    {
        $postdata = http_build_query($postdata);

        $opts = array(
            'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context  = stream_context_create($opts);

        return file_get_contents($url, false, $context);
    }
}
