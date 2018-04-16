<?php
namespace LeKoala\Base\Theme;

use SilverStripe\View\SSViewer;

trait KnowsThemeDir
{
    /**
     * Get current theme dir
     *
     * @return string
     */
    public function getThemeDir()
    {
        $themes = SSViewer::config()->uninherited('themes');
        if(!$themes) {
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

}
