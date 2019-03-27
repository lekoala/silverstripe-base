<?php

namespace LeKoala\Base\Email;

use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Theme\ThemeSiteConfigExtension;

/**
 * Some useful stuff for your emails
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
}
