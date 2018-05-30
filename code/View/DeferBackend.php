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
        $script = "window.addEventListener('DOMContentLoaded', function() { $script });";
        return parent::customScript($script, $uniquenessID);
    }
}
