<?php
namespace LeKoala\Base\Theme;

use SilverStripe\View\SSViewer;

trait KnowsThemeDir
{
    /**
     * Get current theme dir (regardless of current theme set)
     *
     * This will work in admin for instance
     *
     * @return string
     */
    public function getThemeDir()
    {
        // $themes = SSViewer::config()->uninherited('themes');
        $themes = SSViewer::config()->themes;
        if (!$themes) {
            $themes = SSViewer::get_themes();
        }
        if ($themes) {
            do {
                $mainTheme = array_shift($themes);
            } while (strpos($mainTheme, '$') === 0);

            return 'themes/' . $mainTheme;
        }
        return project();
    }

    /**
     * @return boolean
     */
    public function isAdminTheme()
    {
        $themes = SSViewer::get_themes();
        if (empty($themes)) {
            return false;
        }
        $theme = $themes[0];
        return strpos($theme, 'silverstripe/admin') === 0;
    }
}
