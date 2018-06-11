<?php

namespace LeKoala\Base\Privacy;

use Page;
use LeKoala\Base\Extensions\BasePageExtension;
use LeKoala\Base\View\CookieConsent;

class CookiesRequiredPage extends Page
{
    private static $table_name = 'CookiesRequiredPage'; // When using namespace, specify table name

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        // default pages
        if (static::class == self::class && $this->config()->create_default_pages && CookieConsent::config()->cookies_required) {
            if (!$this->hasExtension(BasePageExtension::class)) {
                return;
            }
            $page = $this->requirePageForSegment('cookies-required', static::class, [
                'Title' => 'Cookies required',
                'Content' => 'You need to accept cookies to use this website.',
                'Sort' => 49,
                'ShowInMenus' => 0
            ]);
        }
    }
}
