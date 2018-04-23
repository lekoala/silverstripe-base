<?php
namespace LeKoala\Base\Theme;

use SilverStripe\View\SSViewer;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Admin\AdminRootController;
use SilverStripe\Admin\LeftAndMain;
/**
 * Class \LeKoala\Base\Theme\ThemeControllerExtension
 *
 * @property \SilverStripe\CMS\Controllers\ContentController|\LeKoala\Base\Theme\ThemeControllerExtension $owner
 */
class ThemeControllerExtension extends Extension
{
    use KnowsThemeDir;
    public function onAfterInit()
    {
        // Do nothing in admin
        if(Controller::curr() instanceof LeftAndMain) {
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
     * @param string $cssFile The path to the file with CSS3 variables
     * @return string The path to the file with variable replaced
     */
    protected function replaceVarsInCssFile($cssFile)
    {
        $SiteConfig = $this->owner->SiteConfig();
        $themeDir = $this->getThemeDir();
        // Build the name of the file
        $newName = basename($themeDir) . '/' . basename($cssFile);
        $cssURL = $SiteConfig->getThemeAssetURL() . '/' . $newName;
        $outputFile = $SiteConfig->getThemeAssetsFolder() . '/' . $newName;
        $outputDir = dirname($outputFile);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        // Compare filemtime and SiteConfig last edited
        if (is_file($outputFile)) {
            $buildFileTime = filemtime($outputFile);
            $sourceFileTime = filemtime($cssFile);
            $lastEdited = strtotime($SiteConfig->LastEdited);
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
            $value = $SiteConfig->$dbName;
            // There is no value, use default
            if (!$value) {
                $value = $declarationValue;
            }
            $replaceRegex = "/var\s?\(--" . $declarationName . ",?\s?([a-z-#0-9]*)\)/";
            $replaceCount = 0;
            $cssFileContent = preg_replace($replaceRegex, $value, $cssFileContent, -1, $replaceCount);
        }
        // Minify
        $minifier = Requirements::backend()->getMinifier();
        if ($minifier) {
            $cssFileContent = $minifier->minify($cssFileContent, 'css', $outputFile);
        }
        file_put_contents($outputFile, $cssFileContent);
        return $cssURL;
    }
}
