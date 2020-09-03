<?php

namespace LeKoala\Base\Helpers;

use SilverStripe\Security\Member;

class PasswordHelper
{
    /**
     * Generate a readable password that looks like Mypass15
     *
     * @param integer $len Must be a multiple of 2 or will be increased by one
     * @return string
     */
    public static function generateReadablePassword($len = 8)
    {
        if (($len % 2) !== 0) {
            $len++;
        }

        // Makes room for the two-digit number on the end
        $length = $len - 2;
        $conso = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z');
        $vocal = array('a', 'e', 'i', 'o', 'u');
        $password = '';
        $max = $length / 2;
        for ($i = 1; $i <= $max; $i++) {
            $password .= $conso[random_int(0, 19)];
            $password .= $vocal[random_int(0, 4)];
        }
        // Add a two-digit number
        $password .= random_int(10, 99);
        $newpass = ucfirst($password);
        return $newpass;
    }

    public static function getPasswordRules()
    {
        $validator =  Member::password_validator();

        $rules = [];
        if ($validator->getMinLength()) {
            $rules[] = _t(
                'PasswordHelper.PasswordMinLength',
                '{minimum} or more characters long',
                ['minimum' => $validator->getMinLength()]
            );
        }
        if (count($validator->getTests())) {
            $translatedRules = self::translatePasswordRules($validator->getTestNames());
            $rules[] = _t(
                'PasswordHelper.PasswordTests',
                'must contain {rules}',
                ['rules' => implode(', ', $translatedRules)]
            );
        }
        return implode("; ", $rules);
    }

    protected static function translatePasswordRules(array $rules)
    {
        foreach ($rules as $rule) {
            switch ($rule) {
                case 'lowercase':
                    $translate[] = _t('PasswordHelper.RuleLowercase', 'one lowercase character');
                    break;
                case 'uppercase':
                    $translate[] = _t('PasswordHelper.RuleUppercase', 'one uppercase character');
                    break;
                case 'digits':
                    $translate[] = _t('PasswordHelper.RuleDigits', 'one digit');
                    break;
                case 'punctuation':
                    $translate[] = _t('PasswordHelper.RulePunctuation', 'one special character');
                    break;
                default:
                    $translate[] = $rule;
                    break;
            }
        }
        return $translate;
    }
}
