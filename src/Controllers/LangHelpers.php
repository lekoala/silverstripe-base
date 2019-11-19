<?php

namespace LeKoala\Base\Controllers;

use LeKoala\Base\i18n\BaseI18n;

trait LangHelpers
{
    /**
     * @return string
     */
    public function CurrentLang()
    {
        return substr($this->ContentLocale(), 0, 2);
    }

    /**
     * @param string $lang
     * @return bool
     */
    public function IsCurrentLang($lang)
    {
        return $lang == $this->CurrentLang();
    }

    /**
     * Get the link variant for a given lang
     *
     * @param string $lang
     * @return string
     */
    public function LangLink($lang)
    {
        $locale = BaseI18n::get_locale_from_lang($lang);
        return $this->LocaleLink($locale);
    }
}
