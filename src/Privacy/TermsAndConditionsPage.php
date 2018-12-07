<?php

namespace LeKoala\Base\Privacy;

use Page;
use LeKoala\Base\Extensions\BasePageExtension;

/**
 * Class \LeKoala\Base\Privacy\TermsAndConditionsPage
 *
 */
class TermsAndConditionsPage extends Page
{
    private static $table_name = 'TermsAndConditionsPage'; // When using namespace, specify table name

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        // default pages
        if (static::class == self::class && $this->config()->create_default_pages) {
            if (!$this->hasExtension(BasePageExtension::class)) {
                return;
            }
            $this->requirePageForSegment('legal-terms', static::class, [
                'Title' => 'Terms and Conditions',
                'Content' => 'Please go to https://termsandconditionstemplate.com/generate/ to generate your terms and conditions or copy your own',
                'Sort' => 50,
                'ShowInMenus' => 0
            ], true);
        }
    }

    /**
     * @return string
     */
    public static function getTerms()
    {
        return static::get()->first()->Content;
    }
}
