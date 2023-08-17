<?php

namespace LeKoala\Base\Helpers;

use SilverStripe\Control\Director;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

class PathHelper
{
    /**
     * Keep in mind in admin we don't have our theme active and you can get something like this
     * "/_resources/app/images"
     *
     * @param string $url
     * @return string A public url
     */
    public static function themeURL($url)
    {
        return ThemeResourceLoader::themedResourceURL($url);
    }

    /**
     * <img src="$resourceURL('app/images/my-image.jpg')">
     * <img src="$resourceURL('my/module:images/my-image.jpg')">
     * <img src="$resourceURL('themes/simple/images/my-image.jpg')">
     * <img src="$resourceURL('themes/simple/images')/$Image.jpg">
     *
     * @param string $url
     * @return string A public url
     */
    public static function resourceURL($url)
    {
        return ModuleResourceLoader::resourceURL($url);
    }

    /**
     * @param string $url
     * @return string An absolute path
     */
    public static function themePath($url)
    {
        return Director::baseFolder() . "/public" .  self::themeURL($url);
    }

    /**
     * @param string $url
     * @return string An absolute path
     */
    public static function resourcePath($url)
    {
        return Director::baseFolder() . "/public" .  self::resourceURL($url);
    }
}
