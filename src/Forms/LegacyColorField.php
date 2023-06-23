<?php

namespace LeKoala\Base\Forms;

use SilverStripe\i18n\i18n;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;
use LeKoala\Base\View\CommonRequirements;

/**
 * @deprecated Use ColorField instead
 * @link https://bgrins.github.io/spectrum/
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/color
 */
class LegacyColorField extends TextField
{
    use ConfigurableField;

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Config array
     *
     * @var array
     */
    protected $config = [];

    /**
     * @config
     * @var string
     */
    private static $version = '1.8.0';

    /**
     * @config
     * @var array
     */
    private static $default_config = [
        "preferredFormat" => "hex",
        "showInitial" => true,
        "showInput" => true,
        "allowEmpty" => true,
    ];

    public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null)
    {
        parent::__construct($name, $title, $value, $maxLength, $form);
        $this->mergeDefaultConfig();
    }

    public function getInputType()
    {
        // Use text instead of color to allow empty
        // @link https://github.com/bgrins/spectrum/issues/201
        return 'text';
        // return 'color';
    }

    public function Type()
    {
        return 'spectrum';
    }

    public function getList()
    {
        return $this->getConfig('list');
    }

    public function setList($values)
    {
        $this->setConfig('list', $values);
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
        // Set lang based on locale
        $lang = substr($this->getLocale(), 0, 2);

        $this->setAttribute('data-mb', 'spectrum');
        $this->setAttribute('data-mb-options', $this->getConfigAsJson());

        $version = $this->config()->version;
        Requirements::css("https://cdnjs.cloudflare.com/ajax/libs/spectrum/$version/spectrum.min.css");
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/spectrum/$version/spectrum.min.js");
        if ($lang != 'en') {
        }
        CommonRequirements::modularBehaviour();

        return parent::Field($properties);
    }
}
