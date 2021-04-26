<?php

namespace LeKoala\Base\View;

use SilverStripe\Control\Director;

/**
 * Helper for Statically
 * @link https://statically.io/
 */
class Statically
{

    /**
     * Get website screenshot
     *
     * @link https://statically.io/docs/using-screenshot/
     * @param string $url
     * @param bool $mobile
     * @param bool $full
     * @return string
     */
    public static function screenshot($url, $mobile = false, $full = false)
    {
        $part = [];
        if ($mobile) {
            $part[] = 'device=mobile';
        }
        if ($full) {
            $part[] = 'full=true';
        }
        if (!empty($part)) {
            return 'https://cdn.statically.io/screenshot/'  . implode(",", $part) . '/' . $url;
        }
        return 'https://cdn.statically.io/screenshot/'  . $url;
    }

    /**
     * Get website screenshot
     *
     * @param string $url
     * @param bool $mobile
     * @param bool $full
     * @return string The content of the file
     */
    public static function screenshotData($url, $mobile, $full)
    {
        return file_get_contents(self::screenshot($url, $mobile, $full));
    }

    /**
     * Get a cached image
     *
     * Supported parameters:
     * - h=:pixel
     * - w=:pixel
     * - f=auto
     * - f=webp
     * - q=:percentage
     *
     * @link https://statically.io/docs/using-images/
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function img($url, $params = [])
    {
        $url = preg_replace('#^https?://#', '', $url);

        $p = '';
        if (!empty($params)) {
            $p = implode(",", $params) . "/";
        }

        $urlParts = explode("/", $url, 2);

        $domain = $urlParts[0];
        $image = $urlParts[1];

        return 'https://cdn.statically.io/img/' . $domain . $p . $image;
    }

    /**
     * Get a cached flag
     *
     * This is not documented anymore
     *
     * @deprecated
     * @param string $countryCode
     * @param boolean $svg
     * @param integer $width
     * @return string
     */
    public static function flag($countryCode, $svg = true, $width = 100)
    {
        if ($svg) {
            return 'https://cdn.staticaly.com/misc/flags/' . $countryCode . '.svg';
        }
        return 'https://cdn.staticaly.com/misc/flags/' . $countryCode . '.png?w=' . $width;
    }

    /**
     * Get the favicon of a domain
     *
     * This is not documented anymore
     *
     * @deprecated
     * @param string $url
     * @return string
     */
    public static function favicon($url = null)
    {
        if ($url === null) {
            $url = Director::absoluteBaseURL();
        }
        $url = preg_replace('#^https?://#', '', $url);
        return 'https://cdn.staticaly.com/favicons/' . $url;
    }
}
