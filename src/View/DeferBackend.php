<?php


namespace LeKoala\Base\View;

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

}
