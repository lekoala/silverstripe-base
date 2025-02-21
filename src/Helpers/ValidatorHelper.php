<?php

namespace LeKoala\Base\Helpers;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 *
 */
class ValidatorHelper
{
    public static function isValidEmail(?string $email): bool
    {
        if ($email === null || !str_contains($email, '@')) {
            return false;
        }
        if (class_exists(EmailValidator::class)) {
            $validator = new EmailValidator;
            return $validator->isValid($email, new RFCValidation());
        }
        $result = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (is_bool($result)) {
            return $result;
        }
        return false;
    }

    public static function isValidRfcEmail(?string $email): bool
    {
        if (!str_contains($email, '<')) {
            return self::isValidEmail($email);
        }
        preg_match('/(?:<)(.+)(?:>)$/', $email, $matches);
        if (!empty($matches)) {
            return self::isValidEmail($matches[1]);
        }
        return false;
    }
}
