<?php

namespace LeKoala\Base\Faq;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\View\Requirements;

/**
 * Class \LeKoala\Base\Faq\FaqPageController
 *
 * @property \LeKoala\Base\Faq\FaqPage dataRecord
 * @method \LeKoala\Base\Faq\FaqPage data()
 * @mixin \LeKoala\Base\Faq\FaqPage dataRecord
 */
class FaqPageController extends \PageController
{
    private static $allowed_actions = [
        "index",
    ];

    public function init()
    {
        parent::init();

        /*
        LeKoala\Base\Faq\FaqPageController:
          theme_files: true
        */
        if ($this->config()->theme_files) {
            Requirements::themedCSS('faq.css');
            Requirements::themedJavascript('faq.js');
        }
    }

    public function index(HTTPRequest $request = null)
    {
        // Use non namespaced name
        return $this->renderWith(['FaqPage', 'Page']);
    }
}
