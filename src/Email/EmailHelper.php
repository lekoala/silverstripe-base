<?php

namespace LeKoala\Base\Email;

use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Helper email class
 */
class EmailHelper
{

    /**
     * Get an email with base template and data
     *
     * @param string subject
     * @param string body
     * @return Email
     */
    public static function createEmail($subject = null, $body = null)
    {
        $email = Email::create();
        $email->setHTMLTemplate('Email\\BaseEmail');

        $sc = SiteConfig::current_site_config();
        $data = [
            'PrimaryColor' => $sc->PrimaryColor ?? '#1a1a1a',
        ];
        $email->setData($data);

        if ($subject) {
            $email->setSubject($subject);
        }
        if ($body) {
            $email->addData("EmailContent", $body);
        }

        return $email;
    }

    /**
     * Match all words and whitespace, will be terminated by '<'
     *
     * Note: use /u to support utf8 strings
     *
     * @param string $rfc_email_string
     * @return string
     */
    public static function get_displayname_from_rfc_email($rfc_email_string)
    {
        $name = preg_match('/[\w\s]+/u', $rfc_email_string, $matches);
        $matches[0] = trim($matches[0]);
        return $matches[0];
    }

    /**
     * Extract parts between brackets
     *
     * @param string $rfc_email_string
     * @return string
     */
    public static function get_email_from_rfc_email($rfc_email_string)
    {
        if (strpos($rfc_email_string, '<') === false) {
            return $rfc_email_string;
        }
        preg_match('/(?:<)(.+)(?:>)$/', $rfc_email_string, $matches);
        if (empty($matches)) {
            return $rfc_email_string;
        }
        return $matches[1];
    }

    /**
     * Convert an html email to a text email while keeping formatting and links
     *
     * @param string $content
     * @return string
     */
    public static function convertHtmlToText($content)
    {
        // Prevent styles to be included
        $content = preg_replace('/<style.*>([\s\S]*)<\/style>/i', '', $content);
        // Convert html entities to strip them later on
        $content = html_entity_decode($content);
        // Convert new lines for relevant tags
        $content = str_ireplace(['<br />', '<br/>', '<br>', '<table>', '</table>'], "\r\n", $content);
        // Avoid lots of spaces
        $content = preg_replace('/[\r\n]+/', ' ', $content);
        // Replace links to keep them accessible
        $content = preg_replace('/<a[\s\S]*href="(.*?)"[\s\S]*>(.*)<\/a>/i', '$2 ($1)', $content);
        // Remove html tags
        $content = strip_tags($content);
        // Trim content so that it's nice
        $content = trim($content);
        return $content;
    }
}
