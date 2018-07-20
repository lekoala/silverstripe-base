<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Versioned\Versioned;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Useful utilities for pages
 *
 * @property \SilverStripe\CMS\Model\SiteTree|\LeKoala\Base\Extensions\BasePageExtension $owner
 */
class BasePageExtension extends DataExtension
{

    /**
     * Easily require the page in requireDefaultRecords using this method
     * Even works across multiple subsites
     *
     * @param string $segment Default url segment for the page
     * @param string $class The page class
     * @param array $data Data to inject in the page
     */
    public function requirePageForSegment($segment, $class, $data = [])
    {
        SubsiteHelper::withSubsites(function ($SubsiteID = 0) use ($segment, $class, $data) {
            $page = SiteTree::get_by_link($segment);
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

        $SiteConfig = SiteConfig::current_site_config();
        $descriptionText = $owner->MetaDescription;
        $imageLink = '';
        $shareTitle = $owner->getTitle();

        $tags = '';
        // OpenGraph
        $tags .= "\n<!-- OpenGraph Meta Tags -->\n";
        // og:type
        $siteTitle = $SiteConfig->Title;
        $tags .= $this->createMetaTag('og:site_name', $siteTitle);
        // og:site_name
        $ogType = "website";
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
}
