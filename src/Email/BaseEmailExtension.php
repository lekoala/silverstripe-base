<?php

namespace LeKoala\Base\Email;

use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Theme\ThemeSiteConfigExtension;

/**
 * Some useful stuff for your emails
 *
 * @link https://docs.silverstripe.org/en/4/developer_guides/email/
 * @property \SilverStripe\Control\Email\Email|\LeKoala\Base\Email\BaseEmailExtension $owner
 */
class BaseEmailExtension extends Extension
{

    /**
     * Compat with foundation-email modules
     *
     * @return void
     */
    public function updateFoundationColors(&$colors)
    {
        $sc = SiteConfig::current_site_config();
        if (!$sc->hasExtension(ThemeSiteConfigExtension::class)) {
            return;
        }

        $colors['HeaderBg'] = $sc->PrimaryColor;
        $colors['Header'] = $sc->dbObject('PrimaryColor')->ContrastColor();
        $colors['HeaderBorder'] = $sc->dbObject('PrimaryColor')->HighlightColor(0.8);
        $colors['HeaderLink'] = $sc->dbObject('PrimaryColor')->HighlightColor(0.5);

        $colors['Link'] = $sc->PrimaryColor;

        $colors['BtnBg'] = $sc->PrimaryColor;
        $colors['Btn'] = $sc->dbObject('PrimaryColor')->ContrastColor();
    }

    /**
     * Get body of message after rendering
     * Useful for previews
     *
     * @return string
     */
    public function getRenderedBody()
    {
        $this->owner->render();
        return $this->owner->getSwiftMessage()->getBody();
    }

    /**
     * Don't forget that setBody will erase content of html template
     * Prefer to use this instead. Basically you can replace setBody calls with this method
     * URLs are rewritten by render process
     *
     * Content is stored under EmailContent variable for consistency with base template
     * \vendor\silverstripe\framework\templates\SilverStripe\Control\Email\Email.ss
     * <div class="body">
     * $EmailContent
     * </div>
     *
     * @param string $body
     * @return Email
     */
    public function addBody($body)
    {
        return $this->owner->addData("EmailContent", $body);
    }
}
