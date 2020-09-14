<?php

namespace LeKoala\Base\Extensions;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\i18n\i18n;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use LeKoala\Base\Contact\ContactPage;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Versioned\Versioned;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Privacy\PrivacyNoticePage;
use LeKoala\Base\Privacy\TermsAndConditionsPage;

/**
 * Useful utilities for pages
 *
 * Should be applied to SiteTree. Always applied in base-extensions
 *
 * @property \AboutPage|\AvailableSpacesPage|\HomePage|\Page|\VisionPage|\PortfolioPage|\LeKoala\Base\Blocks\BlocksPage|\LeKoala\Base\Contact\ContactPage|\LeKoala\Base\Faq\FaqPage|\LeKoala\Base\News\NewsPage|\LeKoala\Base\Privacy\CookiesRequiredPage|\LeKoala\Base\Privacy\PrivacyNoticePage|\LeKoala\Base\Privacy\TermsAndConditionsPage|\SilverStripe\ErrorPage\ErrorPage|\SilverStripe\CMS\Model\RedirectorPage|\SilverStripe\CMS\Model\SiteTree|\SilverStripe\CMS\Model\VirtualPage|\LeKoala\Base\Extensions\BasePageExtension $owner
 * @property boolean $ShowInFooter
 */
class BasePageExtension extends DataExtension
{
    private static $db = [
        "ShowInFooter" => "Boolean"
    ];
    private static $casting = [
        "HighlightWordInContent" => "HTMLFragment"
    ];

    public function getPageTitleSeparator()
    {
        return $this->owner->config()->page_title_separator;
    }

    public function updateCMSFields(\SilverStripe\Forms\FieldList $fields)
    {
        // nothing
    }

    public function updateSettingsFields(\SilverStripe\Forms\FieldList $fields)
    {
        // For i18n it is stored under {$ancestorClass}.{$type}_{$name}, so SilverStripe\CMS\Model\SiteTree.db_ShowInFooter
        $fields->insertAfter("ShowInMenus", new CheckboxField("ShowInFooter", $this->owner->fieldLabel("ShowInFooter")));
    }

    /**
     * Easily require the page in requireDefaultRecords using this method
     * Even works across multiple subsites
     *
     * @param string $segment Default url segment for the page
     * @param string $class The page class
     * @param array $data Data to inject in the page
     * @param bool $checkType Check page type instead of segment
     */
    public function requirePageForSegment($segment, $class, $data = [], $checkType = null)
    {
        if ($checkType === null) {
            $checkType = true;
            // only check segment by default if our website is in english
            if (i18n::get_locale() == 'en_US') {
                $checkType = false;
            }
        }
        SubsiteHelper::withSubsites(function ($SubsiteID = 0) use ($segment, $class, $data, $checkType) {
            if ($checkType) {
                $page = DataObject::get_one($class);
            } else {
                $page = SiteTree::get_by_link($segment);
            }
            if ($page) {
                // We have a page but the class does not match
                if ($page->ClassName != $class) {
                    $page->ClassName = $class;
                    $page->writeAll();
                    $page->flushCache();
                    DB::alteration_message($class . ' repaired', 'repaired');
                } else {
                    // Do nothing, a page already exists
                }
            } else {
                $page = new $class();
                $page->SubsiteID = $SubsiteID;
                foreach ($data as $k => $v) {
                    $page->$k = $v;
                }
                $page->URLSegment = $segment;
                $page->writeAll();
                $page->flushCache();

                $site = 'main site';
                if ($SubsiteID) {
                    $site = 'subsite ' . $SubsiteID;
                }

                DB::alteration_message($class . ' created on ' . $site, 'created');
            }
        });
    }

    protected function createMetaTag($property, $content)
    {
        $content = Convert::raw2att($content);
        return "<meta property=\"{$property}\" content=\"{$content}\" />\n";
    }

    /**
     * Update meta tags
     * @link https://github.com/tractorcow/silverstripe-opengraph
     * @param string $tags
     * @return void
     */
    public function MetaTags(&$tags)
    {
        $owner = $this->getOwner();
        $className = $owner->ClassName;
        $ignoredClasses = [ErrorPage::class];
        if (in_array($className, $ignoredClasses)) {
            return;
        }

        $controller = Controller::curr();
        $sourceObject = $owner;
        try {
            if ($controller->hasMethod("getRequestedRecord")) {
                $sourceObject = $controller->getRequestedRecord();
                if (!$sourceObject) {
                    $sourceObject = $owner;
                }
            }
        } catch (Exception $ex) {
            // Keep page as source
        }

        $SiteConfig = SiteConfig::current_site_config();
        $descriptionText = '';
        if ($sourceObject->hasField('MetaDescription')) {
            $descriptionText = $sourceObject->MetaDescription;
        }
        if (!$descriptionText && $sourceObject->hasMethod('getShareDescription')) {
            $descriptionText = $sourceObject->getShareDescription();
        }
        if (!$descriptionText && $sourceObject->hasField('Content')) {
            $descriptionText = preg_replace('/\s+/', ' ', $sourceObject->dbObject('Content')->Summary());
        }
        $imageLink = '';
        if ($sourceObject->hasMethod('getMetaImage')) {
            $imageLink = $sourceObject->getMetaImage();
            if ($imageLink && !is_string($imageLink)) {
                throw new Exception("getMetaImage should return a string");
            }
        }
        $ogType = "website";
        if ($sourceObject->hasMethod('getOGType')) {
            $ogType = $sourceObject->getOGType();
        }
        $shareTitle = $sourceObject->getTitle();
        if ($sourceObject->hasMethod('getShareTitle')) {
            $shareTitle = $sourceObject->getShareTitle();
        }
        $tags = '';
        // Regular tags
        if ($descriptionText) {
            $tags .= $this->createMetaTag("description", $descriptionText);
        }
        // OpenGraph
        $tags .= "\n<!-- OpenGraph Meta Tags -->\n";
        // og:type
        $siteTitle = $SiteConfig->Title;
        $tags .= $this->createMetaTag('og:site_name', $siteTitle);
        // og:site_name
        $tags .= $this->createMetaTag('og:type', $ogType);
        // og:title
        $tags .= $this->createMetaTag('og:title', $shareTitle);
        // og:image
        if (!empty($imageLink)) {
            $tags .= $this->createMetaTag('og:image', $imageLink);
        }
        // og:description
        if (!empty($descriptionText)) {
            $tags .= $this->createMetaTag('og:description', $descriptionText);
        }
        // og:url
        $link = $owner->AbsoluteLink();
        $tags .= $this->createMetaTag('og:url', $link);

        // Twitter
        // @link https://developer.twitter.com/en/docs/tweets/optimize-with-cards/overview/markup.html
        $tags .= "\n<!-- Twitter Meta Tags -->\n";
        // twitter:site
        // @username of website. Either twitter:site or twitter:site:id is required.
        $twitterSite = $SiteConfig->Twitter;
        if ($twitterSite) {
            $tags .= $this->createMetaTag('twitter:site', $twitterSite);
        }
        // twitter:title
        if (!empty($shareTitle)) {
            $tags .= $this->createMetaTag('twitter:title', $shareTitle);
        }
        // twitter:image
        if (!empty($imageLink)) {
            $tags .= $this->createMetaTag('twitter:image', $imageLink);
        }
        // twitter:description
        if (!empty($descriptionText)) {
            $tags .= $this->createMetaTag('twitter:description', $descriptionText);
        }
        // twitter:card - summary / summary_large_image
        $cardType = 'summary';
        if (!empty($imageLink)) {
            $cardType = 'summary_large_image';
        }
        $tags .= $this->createMetaTag('twitter:card', $cardType);
    }

    public function HighlightWordInContent($keyword)
    {
        $content = strip_tags($this->owner->Content);
        $content = preg_replace("/\p{L}*?" . preg_quote($keyword) . "\p{L}*/ui", "<span class=\"search-highlight\">$0</span>", $content);
        $pos = strpos($content, $keyword);
        $start = $pos - 50;
        if ($start < 0) {
            $start = 0;
        }
        $content = substr($content, $start, 255) . '...';
        return $content;
    }
}
