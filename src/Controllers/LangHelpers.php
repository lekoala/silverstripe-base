<?php

namespace LeKoala\Base\Controllers;

use LeKoala\Multilingual\LangHelper;

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
        $locale = LangHelper::get_locale_from_lang($lang);
        if (method_exists($this, 'LocaleLink')) {
            return $this->LocaleLink($locale);
        }
        $link = LangHelper::withLocale($locale, function () {
            return $this->data()->Link();
        });
        // Check that it contains the right prefix
        if (!str_starts_with($link, "/$lang/")) {
            return "/$lang/";
        }
        return $link;
    }
}
