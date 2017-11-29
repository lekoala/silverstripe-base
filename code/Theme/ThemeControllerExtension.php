<?php
namespace LeKoala\Base\Theme;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Director;

/**
 * 
 */
class ThemeControllerExtension extends Extension
{

    public function onAfterInit() {
        $this->requireGoogleFonts();
        $this->requireThemeStyles();
    }

    /**
     * Get current theme dir
     *
     * @return string
     */
    public function getThemeDir()
    {
        $themes = SSViewer::get_themes();
        if ($themes) {
            $mainTheme = array_shift($themes);
            return 'themes/' . $mainTheme;
        }
        return '';
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
        $cssDir = $themeDir . '/css';

        $files = glob($cssDir . '/*.css');

        // Files are included in order, please name them accordingly
        foreach ($files as $file) {
            $name = basename($file);
            if (strpos($file, '-theme.css') !== false) {
                // This file requires variable replacement
                $this->replaceVarsInCssFile($file);
            } else {
                // Simply include it
                Requirements::themedCSS($name);
            }
        }
    }

    protected function replaceVarsInCssFile($cssFile)
    {
        $SiteConfig = $this->owner->SiteConfig();
        $themeDir = $this->getThemeDir();
        $cssFile = Director::baseFolder() . '/' . $cssFile;

        // Build the name of the file
        $css = 'assets/_theme/styles-' . basename($themeDir) . '-' . $SiteConfig->ID . '.css';
        $filename = Director::baseFolder() . '/' . $css;
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
        }

        // Compare filemaketime and SiteConfig last edited
        $buildFileTime = filemtime($filename);
        $sourceFileTime = filemtime($cssFile);
        $lastEdited = strtotime($SiteConfig->LastEdited);

        if ($buildFileTime >= $sourceFileTime && $buildFileTime >= $lastEdited) {
            Requirements::css($css);
            return;
        }

        // $usagesRegex = "/var\s?\((?P<name>[a-z-]*),?\s?(?P<default>[a-z-]*)\)/";

        $cssFileContent = file_get_contents($cssFile);
        
        // Get css variables default values if none are set in SiteConfig
        $declarationRegex = "/--(?P<name>[a-z-]*):\s?(?P<value>[\"'A-Za-z-#0-9(),\s]*)/";
        $declarationsMatches = null;
        preg_match_all($declarationRegex, $cssFileContent, $declarationsMatches);
        $declarations = array_combine($declarationsMatches['name'], $declarationsMatches['value']);

        foreach ($declarations as $declarationName => $declarationValue) {
            $dbName = str_replace(' ', '', ucwords(str_replace('-', ' ', $declarationName)));
            $value = $SiteConfig->$dbName;
            if (!$value) {
                $declarationValue = $value;
            }
            if(strpos($dbName, 'Color') !== false) {
                $value = '#' . $value;
            }
            $replaceRegex = "/var\s?\(--" . $declarationName . ",?\s?([a-z-#0-9]*)\)/";
            $replaceCount = 0;
            $cssFileContent = preg_replace($replaceRegex, $value, $cssFileContent, -1, $replaceCount);
        }
        
        // Minify
        $minifier = Requirements::backend()->getMinifier();
        if ($minifier) {
            $cssFileContent = $minifier->minify($cssFileContent, 'css', $filename);
        }
        \file_put_contents($filename, $cssFileContent);

        Requirements::css($css);
    }

}
