<?php
namespace LeKoala\Base\Theme;

use SilverStripe\View\SSViewer;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Admin\AdminRootController;
use SilverStripe\Admin\LeftAndMain;
use LeKoala\Base\ORM\FieldType\DBColor;
use SilverStripe\ORM\FieldType\DBClassName;

/**
 * Class \LeKoala\Base\Theme\ThemeControllerExtension
 *
 * @property \LeKoala\Base\Controllers\BaseContentController|\LeKoala\Base\Theme\ThemeControllerExtension $owner
 */
class ThemeControllerExtension extends Extension
{
    use KnowsThemeDir;
    public function onAfterInit()
    {
        // Do nothing in admin
        if (Controller::curr() instanceof LeftAndMain) {
            return;
        }
        $this->requireGoogleFonts();
        $this->requireThemeStyles();
    }
    protected function requireGoogleFonts()
    {
        $SiteConfig = $this->owner->SiteConfig();
        if ($SiteConfig->GoogleFonts) {
            Requirements::css('https://fonts.googleapis.com/css?family=' . $SiteConfig->GoogleFonts);
        }
    }
    protected function requireThemeStyles()
    {
        $themeDir = $this->getThemeDir();
        $cssPath = Director::baseFolder() . '/' . $themeDir . '/css';
        $files = glob($cssPath . '/*.css');
        $SiteConfig = $this->owner->SiteConfig();
        // Files are included in order, please name them accordingly
        foreach ($files as $file) {
            // Skip theme files, they should be included through SiteConfig
            if (strpos($file, '-theme.css') !== false) {
                continue;
            }
            $name = basename($file);
            // Skip editor.css
            if ($name == 'editor.css') {
                continue;
            }
            Requirements::themedCSS($name);
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
     * --header-font: "Roboto", serif;
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
        // Get css variables and use default values if they are not set in SiteConfig
        $declarationRegex = "/--(?P<name>[a-z-]*):\s?(?P<value>[\"'A-Za-z-#0-9(),\s]*)/";
        $declarationsMatches = null;
        preg_match_all($declarationRegex, $cssFileContent, $declarationsMatches);
        $declarations = array_combine($declarationsMatches['name'], $declarationsMatches['value']);
        foreach ($declarations as $declarationName => $declarationValue) {
            $dbName = str_replace(' ', '', ucwords(str_replace('-', ' ', $declarationName)));
            $dbObject = $SiteConfig->dbObject($dbName);
            if (!$dbObject) {
                continue;
            }
            $value = $dbObject->getValue();
            // There is no value, use default
            if (!$value) {
                $value = $declarationValue;
            }
            $replaceRegex = "/var\s?\(--{$declarationName}\)/";
            $replaceCount = 0;
            $cssFileContent = preg_replace($replaceRegex, $value, $cssFileContent, -1, $replaceCount);
            // For colors, also add variants
            if ($dbObject instanceof DBColor) {
                // Add contrast
                $val = $dbObject->ContrastColor();
                $regex = "/var\s?\(--{$declarationName}-contrast\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
                // Add highlight
                $val = $dbObject->HighlightColor();
                $regex = "/var\s?\(--{$declarationName}-highlight\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
                // Add muted
                $val = $dbObject->HighlightColor(0.5);
                $regex = "/var\s?\(--{$declarationName}-muted\)/";
                $cssFileContent = preg_replace($regex, $val, $cssFileContent, -1, $replaceCount);
            }
        }
        // Minify
        $minifier = Requirements::backend()->getMinifier();
        if ($minifier) {
            $cssFileContent = $minifier->minify($cssFileContent, 'css', $outputFile);
        }
        $date = date('Y-m-d H:i:s');
        $cssFileContent = "/* Compiled on $date*/\n" . $cssFileContent;
        file_put_contents($outputFile, $cssFileContent);
        return $cssURL;
    }
}
