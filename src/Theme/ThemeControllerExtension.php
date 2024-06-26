<?php

namespace LeKoala\Base\Theme;

use SilverStripe\Core\Extension;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use LeKoala\Base\ORM\FieldType\DBColor;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Controllers\HasLogger;

/**
 * Class \LeKoala\Base\Theme\ThemeControllerExtension
 *
 * @property \LeKoala\Base\Controllers\BaseContentController|\LeKoala\Base\Theme\ThemeControllerExtension $owner
 */
class ThemeControllerExtension extends Extension
{
    use KnowsThemeDir;
    use HasLogger;

    /**
     * @var string
     */
    protected static $customGoogleFont = null;

    /**
     * @var boolean
     */
    protected static $preventLoadingThemeStyles = false;

    /**
     * @return string
     */
    public static function getCustomGoogleFont()
    {
        return self::$customGoogleFont;
    }

    /**
     * Set a custom font to be included
     *
     * @param string $googleFont The strings that comes after ?family=
     * @return void
     */
    public static function setCustomGoogleFont($googleFont)
    {
        self::$customGoogleFont = $googleFont;
    }

    /**
     * @return bool
     */
    public static function getPreventLoadingThemeStyles()
    {
        return self::$preventLoadingThemeStyles;
    }

    /**
     * @param bool $preventLoadingThemeStyles
     * @return void
     */
    public static function setPreventLoadingThemeStyles($preventLoadingThemeStyles = true)
    {
        self::$preventLoadingThemeStyles = $preventLoadingThemeStyles;
    }

    /**
     * @return void
     */
    public function onAfterInit()
    {
        if ($this->isAdminTheme()) {
            return;
        }
        $this->requireGoogleFonts();
        $this->requireThemeStyles();
    }

    /**
     * @return void
     */
    protected function requireGoogleFonts()
    {
        $googleFont = self::$customGoogleFont;
        if (!$googleFont) {
            //@phpstan-ignore-next-line
            $SiteConfig = $this->owner->SiteConfig();
            $googleFont = $SiteConfig->GoogleFonts;
        }
        if ($googleFont) {
            //@link https://www.cdnplanet.com/blog/faster-google-webfonts-preconnect/
            Requirements::insertHeadTags('<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />');
            Requirements::css('https://fonts.googleapis.com/css?family=' . $googleFont . "&display=swap");
        }
    }

    /**
     * @return void
     */
    protected function requireThemeStyles()
    {
        if (self::$preventLoadingThemeStyles) {
            return;
        }

        $themeDir = $this->getThemeDir();
        $cssPath = Director::baseFolder() . '/' . $themeDir . '/css';
        //@phpstan-ignore-next-line
        $SiteConfig = $this->owner->SiteConfig();

        /**
         * You can disable this by setting. Make sure to do it AFTER #base-theme
         *
         * SilverStripe\SiteConfig\SiteConfig:
         *   auto_include_css: false
         */
        $ignore = [];
        if (SiteConfig::config()->auto_include_css) {
            $files = glob($cssPath . '/*.css');
            if ($files) {
                // Files are included in order, please name them accordingly
                foreach ($files as $file) {
                    // Skip ignored files
                    if (in_array($file, $ignore)) {
                        continue;
                    }
                    // Skip theme files, they should be included through SiteConfig
                    if (strpos($file, '-theme.css') !== false) {
                        continue;
                    }
                    if (strpos($file, '-theme.min.css') !== false) {
                        continue;
                    }
                    // Skip unminified files if we have a min file
                    $minFile = str_replace('.css', '.min.css', $file);
                    if (in_array($minFile, $files)) {
                        // in dev, favor non minified files
                        // if (Director::isDev()) {
                        //     $ignore[] = $minFile;
                        // } else {
                        continue;
                        // }
                    }
                    $name = basename($file);
                    // Skip editor.css
                    if ($name == 'editor.css' || $name == "editor.min.css") {
                        continue;
                    }
                    // themedCSS use filename without extension
                    $name = pathinfo($name, PATHINFO_FILENAME);
                    Requirements::themedCSS($name);
                }
            }
        }
        if ($SiteConfig->CssTheme) {
            $themedFile = $cssPath . '/' . $SiteConfig->CssTheme;
            Requirements::css($this->replaceVarsInCssFile($themedFile));
        }
    }
    /**
     * This allows to use CSS3 variable as configurable variables in your themes
     *
     * :root {
     * --header-font-family: "Roboto", serif;
     * }
     *
     * Will look for the HeaderFont property and be replaced accordingly
     *
     * Colors have additionnal functionnalities:
     *
     * - Replace --my-color-contrast with ContrastColor
     *
     * @param string $cssFile The path to the file with CSS3 variables
     * @return string The path to the file with variable replaced
     */
    protected function replaceVarsInCssFile($cssFile)
    {
        //@phpstan-ignore-next-line
        $SiteConfig = $this->owner->SiteConfig();
        $lastEdited = strtotime($SiteConfig->LastEdited);
        $themeDir = $this->getThemeDir();
        // Build the name of the file
        $newName = basename($themeDir) . '/' . basename($cssFile);
        $cssURL = $SiteConfig->getThemeAssetURL() . '/' . $newName;
        $cssURL .= "?m=" . $lastEdited;
        $outputFile = $SiteConfig->getThemeAssetsFolder() . '/' . $newName;
        $outputDir = dirname($outputFile);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $forceCompile = false;
        if (isset($_GET['compile']) && Director::isDev()) {
            $forceCompile = true;
        }
        // Compare filemtime and SiteConfig last edited
        if (is_file($outputFile) && !$forceCompile) {
            $buildFileTime = filectime($outputFile);
            $sourceFileTime = filectime($cssFile);
            // Nothing has changed, return the output file url
            if ($buildFileTime >= $sourceFileTime && $buildFileTime >= $lastEdited) {
                return $cssURL;
            }
        }
        $cssFileContent = file_get_contents($cssFile);
        if (!$cssFileContent) {
            return $cssURL;
        }
        // Get css variables and use default values if they are not set in SiteConfig
        $declarationRegex = "/--(?P<name>[a-z-]*):\s?(?P<value>[\"'A-Za-z-#0-9(),\s]*)/";
        $declarationsMatches = [];
        preg_match_all($declarationRegex, $cssFileContent, $declarationsMatches);
        $declarations = array_combine($declarationsMatches['name'], $declarationsMatches['value']);
        foreach ($declarations as $declarationName => $declarationValue) {
            $dbName = str_replace(' ', '', ucwords(str_replace('-', ' ', $declarationName)));
            $dbObject = $SiteConfig->dbObject($dbName);
            if (!$dbObject) {
                continue;
            }
            $value = $dbObject->getValue();
            // There is no value and no default, continue
            if (!$value && !$declarationValue) {
                self::getLogger()->debug("$declarationName has no value in your theme files");
                continue;
            }
            // There is no value, use default
            if (!$value) {
                $value = $declarationValue;
                // Object must use colors so that colors variants are consistent
                $dbObject->setValue($declarationValue);
            }
            $replaceRegex = "/var\s?\(--{$declarationName}\)/";
            $replaceCount = 0;
            $cssFileContent = preg_replace($replaceRegex, $value, $cssFileContent, -1, $replaceCount);
            // For colors, also add variants
            // It's a lot of regexes, but it's better than compiling ourselves
            if ($dbObject instanceof DBColor) {
                // Add contrast
                $val = $dbObject->ContrastColor();
                $regex = "/var\s?\(--{$declarationName}-contrast\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
                // Add highlight
                $val = $dbObject->HighlightColor();
                $regex = "/var\s?\(--{$declarationName}-highlight\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
                // Add highlight contrast
                $val = $dbObject->HighlightContrastColor();
                $regex = "/var\s?\(--{$declarationName}-highlight-contrast\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
                // Add lowlight
                $val = $dbObject->LowlightColor();
                $regex = "/var\s?\(--{$declarationName}-lowlight\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
                // Add lowlight contrast
                $val = $dbObject->LowlightColorContrastColor();
                $regex = "/var\s?\(--{$declarationName}-lowlight-contrast\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
                // Add muted
                $val = $dbObject->HighlightColor(0.5);
                $regex = "/var\s?\(--{$declarationName}-muted\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
                // Add transparent
                $val = $dbObject->CSSColor(0.8);
                $regex = "/var\s?\(--{$declarationName}-transparent\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
            }
        }
        // Minify
        $backend = Requirements::backend();
        if (method_exists($backend, "getMinifier")) {
            $minifier = $backend->getMinifier();
            if ($minifier) {
                $cssFileContent = $minifier->minify($cssFileContent, 'css', $outputFile);
            }
        }

        // Remove map
        $cssFileContent = preg_replace("/\/\*#.*\*\//", "", $cssFileContent);

        // Add timestamp
        $date = date('Y-m-d H:i:s');
        $cssFileContent = "/* Compiled on $date*/\n" . $cssFileContent;
        file_put_contents($outputFile, $cssFileContent);
        return $cssURL;
    }
}
