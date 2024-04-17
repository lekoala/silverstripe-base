<?php

namespace LeKoala\Base\Helpers;

use SilverStripe\Control\Director;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

class PathHelper
{
    /**
     * @param string|null $url
     */
    public static function absoluteURL(?string $url = null): string
    {
        $url = $url ?? "/";
        $result = Director::absoluteURL($url);
        if (is_bool($result)) {
            $result = "/";
        }
        return $result;
    }

    /**
     * Keep in mind in admin we don't have our theme active and you can get something like this
     * "/_resources/app/images"
     *
     * @param string $url
     * @return string A public url
     */
    public static function themeURL($url): string
    {
        return ThemeResourceLoader::themedResourceURL($url) ?? "";
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
        //@link https://github.com/silverstripe/silverstripe-framework/pull/11187
        //@phpstan-ignore-next-line
        return ModuleResourceLoader::resourceURL($url) ?? "";
    }

    /**
     * @param string $url
     * @return string An absolute public url
     */
    public static function absoluteThemeURL($url)
    {
        return self::absoluteURL(self::themeURL($url));
    }

    /**
     * @param string $url
     * @return string An absolute public url
     */
    public static function absoluteResourceURL($url)
    {
        return self::absoluteURL(self::resourceURL($url));
    }

    /**
     * @param string $url
     * @return string An absolute path
     */
    public static function themePath($url)
    {
        return Director::baseFolder() . "/public" .  strtok(self::themeURL($url), '?');
    }

    /**
     * @param string $url
     * @return string An absolute path
     */
    public static function resourcePath($url)
    {
        return Director::baseFolder() . "/public" .  strtok(self::resourceURL($url), '?');
    }
}
