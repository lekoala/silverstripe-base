<?php

use LeKoala\Base\Tags\Tag;
use LeKoala\Base\Faq\FaqItem;
use LeKoala\Base\News\NewsItem;
use LeKoala\Base\Faq\FaqCategory;
use SilverStripe\Security\Member;
use LeKoala\Base\News\NewsCategory;
use LeKoala\Base\Admin\BaseModelAdmin;
use LeKoala\Base\Contact\ContactSubmission;

/**
 * Get a global overview of your website DataObjects
 *
 * You can extends this with your own models
 *
 * SiteAdmin:
 *   managed_models:
 *     - 'MyCustomModel'
 *
 */
class SiteAdmin extends BaseModelAdmin
{
    private static $managed_models = [
        Member::class,
        ContactSubmission::class,
        NewsItem::class,
        NewsCategory::class,
        FaqCategory::class,
        FaqItem::class,
        Tag::class,
    ];
    private static $url_segment = 'site';
    private static $menu_title = 'Site';
    /**
     * @link https://boxicons.com/cheatsheet/
     * @var string
     */
    private static $menu_icon_class = "bx bx-data";
}
