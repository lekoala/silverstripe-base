<?php
namespace LeKoala\Base\Faq;

/**
 *
 */
class FaqPageController extends \PageController
{
    private static $allowed_actions = [
        "index",
    ];

    public function index()
    {
        // Use non namespaced name
        return $this->renderWith(['FaqPage', 'Page']);
    }

}
