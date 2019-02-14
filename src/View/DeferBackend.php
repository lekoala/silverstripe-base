<?php


namespace LeKoala\Base\View;

use SilverStripe\View\HTML;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\View\Requirements_Backend;

/**
 * A backend that defers everything by default
 *
 * @link https://flaviocopes.com/javascript-async-defer/
 */
class DeferBackend extends Requirements_Backend
{
    // It's better to write to the head with defer
    public $writeJavascriptToBody = false;

    public function javascript($file, $options = array())
    {
        // We want to defer by default
        if (!isset($options['defer'])) {
            $options['defer'] = true;
        }
        return parent::javascript($file, $options);
    }

    public function customScript($script, $uniquenessID = null)
    {
        // Wrap script in a DOMContentLoaded
        // @link https://stackoverflow.com/questions/41394983/how-to-defer-inline-javascript
        if (strpos($script, 'window.addEventListener') === false) {
            $script = "window.addEventListener('DOMContentLoaded', function() { $script });";
        }

        // Remove comments if any
        $script = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $script);

        return parent::customScript($script, $uniquenessID);
    }

    public function getCSS()
    {
        $css = array_diff_key($this->css, $this->blocked);
        // Theme and assets files should always come last to have a proper cascade
        $allCss = [];
        $themeCss = [];
        foreach ($css as $file => $arr) {
            if (strpos($file, 'themes') === 0 || strpos($file, '/assets') === 0) {
                $themeCss[$file] = $arr;
            } else {
                $allCss[$file] = $arr;
            }
        }
        return array_merge($allCss, $themeCss);
    }

    /**
     * Update the given HTML content with the appropriate include tags for the registered
     * requirements. Needs to receive a valid HTML/XHTML template in the $content parameter,
     * including a head and body tag.
     *
     * @param string $content HTML content that has already been parsed from the $templateFile
     *                             through {@link SSViewer}
     * @return string HTML content augmented with the requirements tags
     */
    public function includeInHTML($content)
    {
        // Skip if content isn't injectable, or there is nothing to inject
        $tagsAvailable = preg_match('#</head\b#', $content);
        $hasFiles = $this->css || $this->javascript || $this->customCSS || $this->customScript || $this->customHeadTags;
        if (!$tagsAvailable || !$hasFiles) {
            return $content;
        }
        $requirements = '';
        $jsRequirements = '';

        // Combine files - updates $this->javascript and $this->css
        $this->processCombinedFiles();

        // Script tags for js links
        foreach ($this->getJavascript() as $file => $attributes) {
            // Build html attributes
            $htmlAttributes = [
                'type' => isset($attributes['type']) ? $attributes['type'] : "application/javascript",
                'src' => $this->pathForFile($file),
            ];
            if (!empty($attributes['async'])) {
                $htmlAttributes['async'] = 'async';
            }
            if (!empty($attributes['defer'])) {
                $htmlAttributes['defer'] = 'defer';
            }
            $jsRequirements .= HTML::createTag('script', $htmlAttributes);
            $jsRequirements .= "\n";
        }

        // Add all inline JavaScript *after* including external files they might rely on
        foreach ($this->getCustomScripts() as $script) {
            $jsRequirements .= HTML::createTag(
                'script',
                ['type' => 'application/javascript'],
                "//<![CDATA[\n{$script}\n//]]>"
            );
            $jsRequirements .= "\n";
        }

        // Custom head tags (comes first)
        foreach ($this->getCustomHeadTags() as $customHeadTag) {
            $requirements .= "{$customHeadTag}\n";
        }

        // CSS file links
        foreach ($this->getCSS() as $file => $params) {
            $htmlAttributes = [
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => $this->pathForFile($file),
            ];
            if (!empty($params['media'])) {
                $htmlAttributes['media'] = $params['media'];
            }
            $requirements .= HTML::createTag('link', $htmlAttributes);
            $requirements .= "\n";
        }

        // Literal custom CSS content
        foreach ($this->getCustomCSS() as $css) {
            $requirements .= HTML::createTag('style', ['type' => 'text/css'], "\n{$css}\n");
            $requirements .= "\n";
        }

        // Inject CSS  into body
        $content = $this->insertTagsIntoHead($requirements, $content);

        // Inject scripts
        if ($this->getForceJSToBottom()) {
            $content = $this->insertScriptsAtBottom($jsRequirements, $content);
        } elseif ($this->getWriteJavascriptToBody()) {
            $content = $this->insertScriptsIntoBody($jsRequirements, $content);
        } else {
            $content = $this->insertTagsIntoHead($jsRequirements, $content);
        }
        return $content;
    }
}
