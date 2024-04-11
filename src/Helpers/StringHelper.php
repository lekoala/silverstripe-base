<?php

namespace LeKoala\Base\Helpers;

/**
 * Helps to deal with strings
 */
class StringHelper
{
    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        if ($needle === null) {
            return false;
        }
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        if ($needle === null) {
            return false;
        }
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Add a / slash at the end for happy SEO
     *
     * @param string $url
     * @return string
     */
    public static function trailingSlash($url)
    {
        return rtrim($url, '/') . '/';
    }

    /**
     * @param string $text
     * @return string
     */
    public static function makeLinksClickable($text)
    {
        $text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1:", $text);

        // pad it with a space so we can match things at the start of the 1st line.
        $text = " " . $text;

        // replace links
        $text = preg_replace("/(^|[\n ])([\w]*?)([\w]*?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "$1$2<a href=\"$3\" >$3</a>", $text);
        $text = preg_replace("/(^|[\n ])([\w]*?)((www|wap)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"http://$3\" >$3</a>", $text);
        $text = preg_replace("/(^|[\n ])([\w]*?)((ftp)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"$4://$3\" >$3</a>", $text);
        $text = preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\">$2@$3</a>", $text);
        $text = preg_replace("/(^|[\n ])(mailto:[a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"$2@$3\">$2@$3</a>", $text);
        $text = preg_replace("/(^|[\n ])(skype:[^ \,\"\t\n\r<]*)/i", "$1<a href=\"$2\">$2</a>", $text);

        // Remove our padding..
        $text = substr($text, 1);
        return $text;
    }

    /**
     * Alternative to deprecated utf8_encode
     *
     * @param string $str
     * @return string
     */
    public static function toUtf8($str)
    {
        return mb_convert_encoding($str, "UTF-8");
    }

    public static function truncate(?string $string, int $chars = 120, string $append = "..."): string
    {
        if ($string === null) {
            return '';
        }
        $string = strip_tags($string);
        if (strlen($string) > $chars) {
            return substr($string, 0, $chars) . $append;
        }
        return $string;
    }
}
