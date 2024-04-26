<?php

namespace LeKoala\Base\Privacy;

use Page;
use LeKoala\Base\Extensions\BasePageExtension;

/**
 * Class \LeKoala\Base\Privacy\PrivacyNoticePage
 *
 * @mixin \LeKoala\Base\Extensions\BaseDataObjectExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\CMS\Model\SiteTreeLinkTracking
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 */
class PrivacyNoticePage extends Page
{
    /**
     * @var string
     */
    private static $table_name = 'PrivacyNoticePage'; // When using namespace, specify table name

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        // default pages
        if (static::class == self::class && $this->config()->create_default_pages) {
            if (!$this->hasExtension(BasePageExtension::class)) {
                return;
            }
            $page = $this->requirePageForSegment('privacy-notice', static::class, [
                'Title' => 'Privacy Notice',
                'Content' => 'Please go to  https://termsandconditionstemplate.com/privacy-policy-generator/ to generate your privacy policy or copy your own',
                'Sort' => 49,
                'ShowInMenus' => 0
            ], true);
        }
    }

    /**
     * @return string
     */
    public static function getNotice()
    {
        return static::get()->first()->Content;
    }
}
