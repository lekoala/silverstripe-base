<?php

namespace LeKoala\Base\Faq;

use LeKoala\Multilingual\LangHelper;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class \LeKoala\Base\Faq\FaqPageController
 *
 * @property \LeKoala\Base\Faq\FaqPage $dataRecord
 * @method \LeKoala\Base\Faq\FaqPage data()
 * @mixin \LeKoala\Base\Faq\FaqPage
 */
class FaqPageController extends \PageController
{
    private static $allowed_actions = [
        "index",
    ];

    protected function init()
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
        Requirements::insertHeadTags('<script type="application/ld+json">' . $this->FaqSchemaMarkup() . '</script>', 'FaqSchemaMarkup');

        // Use non namespaced name
        return $this->renderWith(['FaqPage', 'Page']);
    }

    /**
     * @link https://developers.google.com/search/docs/guides/intro-structured-data#markup-formats-and-placement
     * @link https://developers.google.com/search/docs/data-types/faqpage
     * @return string
     */
    public function FaqSchemaMarkup()
    {
        $page = $this->dataRecord;

        $arr = [];
        $arr['@context'] = "https://schema.org";
        $arr['@type'] = "FAQPage";
        $arr['@id'] = Director::absoluteURL($this->Link() ?? "");
        $arr["inLanguage"] = LangHelper::get_locale();
        $arr['name'] = $page->getTitle();
        $arr['description'] = $page->getShareDescription();

        $bc = [];
        foreach ($page->getBreadcrumbItems() as $it) {
            $bc[] = $it->getTitle();
        }
        $arr['breadcrumb'] = implode(" > ", $bc);

        $faqItems = $page->Items()->toArray();

        $arr['mainEntity'] = [];
        foreach ($faqItems as $faq) {
            $arr['mainEntity'][] = [
                "@type" => "Question",
                "name" => $faq->Title,
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $faq->Content
                ]
            ];
        }

        return json_encode($arr);
    }
}
