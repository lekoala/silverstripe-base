<?php

namespace LeKoala\Base\Forms;

use SilverStripe\i18n\i18n;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

/**
 * @link https://github.com/mdbassit/Coloris
 * @link https://gist.github.com/lekoala/233b0c6246170716c52dbfab342caf22
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/color
 */
class ColorField extends TextField
{
    use ConfigurableField;

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * @config
     * @var array
     */
    private static $default_config = [];

    public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null)
    {
        parent::__construct($name, $title, $value, $maxLength, $form);
        $this->mergeDefaultConfig();
    }

    public function getInputType()
    {
        return 'text';
    }

    public function Type()
    {
        return 'coloris';
    }

    public function getSwatches()
    {
        return $this->getConfig('swatches');
    }

    public function setSwatches($values)
    {
        $this->setConfig('swatches', $values);
    }

    /**
     * Get locale to use for this field
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Determines the presented/processed format based on locale defaults,
     * instead of explicitly setting {@link setDateFormat()}.
     * Only applicable with {@link setHTML5(false)}.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    public function Field($properties = array())
    {
        self::requirements();

        $html = parent::Field($properties);
        $config = $this->getConfigAsJson();

        // Simply wrap with custom element and set config
        $html = "<color-input data-config='" . json_encode($config) . "'>" . $html . '</color-input>';

        return $html;
    }

    public static function requirements()
    {
        Requirements::javascript("lekoala/silverstripe-base: javascript/custom-elements/coloris-input.min.js");
    }
}
