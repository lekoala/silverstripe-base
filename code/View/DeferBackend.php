<?php


namespace LeKoala\Base\View;

use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\View\Requirements_Backend;

class DeferBackend extends Requirements_Backend
{
    public $writeJavascriptToBody = false;

    public function themedJavascript($name, $type = null)
    {
        $path = ThemeResourceLoader::inst()->findThemedJavascript($name, SSViewer::get_themes());
        if ($path) {
            $opts = [
                'defer' => true
            ];
            if ($type) {
                $opts['type'] = $type;
            }
            $this->javascript($path, $opts);
        } else {
            throw new InvalidArgumentException(
                "The javascript file doesn't exist. Please check if the file $name.js exists in any "
                . "context or search for themedJavascript references calling this file in your templates."
            );
        }
    }
}
