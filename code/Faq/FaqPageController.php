<?php
namespace LeKoala\Base\Faq;
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
    public function index()
    {
        // Use non namespaced name
        return $this->renderWith(['FaqPage', 'Page']);
    }
}