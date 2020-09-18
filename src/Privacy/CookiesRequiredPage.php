<?php

namespace LeKoala\Base\Privacy;

use Page;
use LeKoala\Base\View\CookieConsent;
use SilverStripe\Control\HTTPRequest;
use LeKoala\Base\Extensions\BasePageExtension;

/**
 * Class \LeKoala\Base\Privacy\CookiesRequiredPage
 *
 */
class CookiesRequiredPage extends Page
{
    private static $table_name = 'CookiesRequiredPage'; // When using namespace, specify table name

    public function index(HTTPRequest $request = null)
    {
        $this->ExtraMeta .= '<meta name="robots" content="noindex, nofollow" />';
        return $this;
    }

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
                'Content' => _t('CookiesRequiredPage.COOKIES_ARE_REQUIRED', 'Cookies are required to use this website'),
                'Sort' => 49,
                'ShowInMenus' => 0
            ], true);
        }
    }
}
