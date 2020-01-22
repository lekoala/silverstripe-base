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
     * @param string $url
     * @return string
     */
    public static function screenshot($url)
    {
        return 'https://cdn.staticaly.com/screenshot/' . $url;
    }

    /**
     * Get website screenshot
     *
     * @param string $url
     * @return string The content of the file
     */
    public static function screenshotData($url)
    {
        return file_get_contents(self::screenshot($url));
    }

    /**
     * Get a cached flag
     *
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
     * Get a cached image
     *
     * Supported parameters:
     * - w
     * - h
     * - quality (between 0 and 100)
     * - crop x,y,w,h
     * - format=webp
     * - filter=grayscale
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function img($url, $params = [])
    {
        $url = preg_replace('#^https?://#', '', $url);
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return 'https://cdn.staticaly.com/img/' . $url;
    }

    /**
     * Get the favicon of a domain
     *
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
