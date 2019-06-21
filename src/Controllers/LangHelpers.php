<?php

namespace LeKoala\Base\Controllers;

trait LangHelpers
{
    /**
     * @return string
     */
    public function CurrentLang()
    {
        return substr($this->ContentLocale(), 0, 2);
    }
}
