<?php

namespace LeKoala\Base\Forms;

use SilverStripe\i18n\i18n;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;
use LeKoala\Base\View\CommonRequirements;

/**
 * Format input using input mask
 *
 * Fully decouples formatted field from data field.
 * Formatting is a UI concept that should not be dealt with in PHP outside of the scope of validation.
 * This avoids messy conversion (for date, currency, ...)
 */
class InputMaskField extends TextField
{
    use ConfigurableField;

    // Base masks
    const MASK_NUMERIC = '9';
    const MASK_ALPHA = 'a';
    const MASK_ALPHANUMERIC = '*';
    // Base alias
    const ALIAS_URL = 'url';
    const ALIAS_IP = 'ip';
    const ALIAS_EMAIL = 'email';
    const ALIAS_DATETIME = 'datetime';
    const ALIAS_NUMERIC = 'numeric';
    const ALIAS_CURRENCY = 'currency';
    const ALIAS_DECIMAL = 'decimal';
    const ALIAS_INTEGER = 'integer';
    const ALIAS_PERCENTAGE = 'percentage';
    const ALIAS_PHONE = 'phone';
    const ALIAS_PHONEBE = 'phonebe';
    const ALIAS_REGEX = 'regex';

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Format to use when unmasking
     *
     * @var string
     */
    protected $dataFormat;

    /**
     * @config
     * @var string
     */
    private static $version = '4.0.9';

    /**
     * @config
     * @var boolean
     */
    private static $use_v5 = true;


    public function Type()
    {
        return 'inputmask';
    }

    public function extraClass()
    {
        return 'text ' . parent::extraClass();
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

    public function getDataFormat()
    {
        return $this->dataFormat;
    }

    /**
     * The value you want when unmasking to hidden field
     *
     * @param string $value The alias or "masked" to get the masked value as is
     * @return $this
     */
    public function setDataFormat($value)
    {
        $this->dataFormat = $value;
        return $this;
    }

    public function getAlias()
    {
        return $this->getConfig('alias');
    }

    public function setAlias($value)
    {
        return $this->setConfig('alias', $value);
    }

    public function getRegex()
    {
        return $this->getConfig('regex');
    }

    /**
     * Use a regular expression as a mask
     *
     * @link https://github.com/RobinHerbots/Inputmask#regex
     * @param string $value
     * @return $this
     */
    public function setRegex($value)
    {
        return $this->setConfig('regex', $value);
    }

    public function getMask()
    {
        return $this->getConfig('mask');
    }

    /**
     * Set the mask
     *
     * 9: numeric, a: alphabetical, *: alphanumeric, (aaa): optional part
     *
     * @param string $value
     * @return $this
     */
    public function setMask($value)
    {
        return $this->setConfig('mask', $value);
    }

    public function getRightAlign()
    {
        return $this->getConfig('rightAlign');
    }

    public function setRighAlign($value)
    {
        return $this->setConfig('rightAlign', $value);
    }

    public function getGroupSeparator()
    {
        return $this->getConfig('groupSeparator');
    }

    public function setGroupSeparator($value)
    {
        return $this->setConfig('groupSeparator', $value);
    }

    public function getRadixPoint()
    {
        return $this->getConfig('radixPoint');
    }

    public function setRadixPoint($value)
    {
        return $this->setConfig('radixPoint', $value);
    }

    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $attributes['lang'] = i18n::convert_rfc1766($this->getLocale());

        // We need to keep real name because unformatted field will set 0 value
        // if not defined
        $attributes['data-name'] = $attributes['name'];
        $attributes['name'] = $attributes['name'];

        return $attributes;
    }

    public function Field($properties = array())
    {
        $this->setAttribute('data-mb', 'inputmask');
        $this->setAttribute('data-mb-options', $this->getConfigAsJson());
        if ($this->dataFormat) {
            $this->setAttribute('data-dataformat', $this->dataFormat);
        }
        self::requirements();
        return parent::Field($properties);
    }

    public static function requirements()
    {
        $useV5 = self::config()->use_v5;
        $version = self::config()->version;

        if ($useV5) {
            // in v5, lib name is jquery.inputmask
            Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js");
            // Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/inputmask.min.js");
            // Use version alias from jsdelivr
            // Requirements::javascript("https://cdn.jsdelivr.net/npm/inputmask@5/dist/inputmask.min.js");
        } else {
            Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/inputmask/$version/jquery.inputmask.bundle.min.js");
        }
        CommonRequirements::modularBehaviour();
        Requirements::javascript('base/javascript/fields/InputMaskField.js');
    }
}
