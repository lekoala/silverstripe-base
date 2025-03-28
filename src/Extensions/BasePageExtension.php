<?php

namespace LeKoala\Base\Extensions;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\i18n\i18n;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Forms\CheckboxField;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Model\Subsite;

/**
 * Useful utilities for pages
 *
 * Should be applied to SiteTree. Always applied in base-extensions
 *
 * @property \SilverStripe\CMS\Model\SiteTree|\LeKoala\Base\Extensions\BasePageExtension $owner
 * @property bool|int $ShowInFooter
 */
class BasePageExtension extends Extension
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
            $subsite = Subsite::get_by_id($SubsiteID);
            if ($subsite && $subsite->IgnoreDefaultPages) {
                return;
            }
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

    public function getShareDescription()
    {
        if ($this->owner->MetaDescription) {
            return $this->owner->MetaDescription;
        }
        return preg_replace('/\s+/', ' ', $this->owner->dbObject('Content')->Summary());
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
        $descriptionText = trim($descriptionText);
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
