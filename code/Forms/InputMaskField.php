<?php
namespace LeKoala\Base\Forms;

use SilverStripe\i18n\i18n;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

/**
 * Format input using input mask
 *
 * Fully decouples formatted field from data field.
 * Formatting is a UI concept that should not be dealt with in PHP outside of the scope of validation.
 * This avoids messy conversion (for date, currency, ...)
 */
class InputMaskField extends TextField
{
    // Base masks
    const MASK_NUMERIC = '9';
    const MASK_ALPHA = 'a';
    const MASK_ALPHANUMERIC = '*';
    // Base alias
    const ALIAS_URL = 'url';
    const ALIAS_IP = 'ip';
    const ALIAS_EMAIL = 'email';
    const ALIAS_DATE = 'date'; // alias of dd/mm/yyyy
    const ALIAS_DATE_DDMMYYYY = 'dd/mm/yyyy';
    const ALIAS_DATE_MMDDYYYY = 'mm/dd/yyyy';
    const ALIAS_DATE_YYYYMMDD = 'yyyy/mm/dd';
    const ALIAS_DATE_ISO = 'yyyy-mm-dd';
    const ALIAS_DATETIME = 'datetime'; // dd/mm/yyyy hh:mm
    const ALIAS_TIME = 'hh:mm:ss';
    const ALIAS_TIME_SHORT = 'hh:mm';
    const ALIAS_NUMERIC = 'numeric';
    const ALIAS_CURRENCY = 'currency';
    const ALIAS_DECIMAL = 'decimal';
    const ALIAS_INTEGER = 'integer';
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
     * Input mask data config
     *
     * @var array
     */
    protected $config = [];

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
    private static $version = '4.0.1-beta.7';

    public function Type()
    {
        return 'inputmask';
    }

    public function extraClass()
    {
        return 'text ' . parent::extraClass();
    }

    public function getConfig($key)
    {
        if (isset($this->config)) {
            return $this->config[$key];
        }
    }

    public function setConfig($key, $value = null)
    {
        if ($value !== null) {
            $this->config[$key] = $value;
        } else {
            unset($this->config[$key]);
        }
        return $this;
    }


    /**
     * Get locale to use for this field
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ? : i18n::get_locale();
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
     * @param string $value
     * @return self
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

    public function getMask()
    {
        return $this->getConfig('mask');
    }

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

    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $attributes['lang'] = i18n::convert_rfc1766($this->getLocale());

        return $attributes;
    }

    public function Field($properties = array())
    {
        $this->setAttribute('data-module', 'inputmask');
        $this->setAttribute('data-config', json_encode($this->config));
        if ($this->dataFormat) {
            $this->setAttribute('data-dataformat', $this->dataFormat);
        }

        $version = self::config()->version;
        // cdnjs does not maintain new versions
        // Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/$version/jquery.inputmask.bundle.min.js");
        // unpkg does not support beta version
        // Requirements::javascript("https://unpkg.com/inputmask@$version/dist/min/jquery.inputmask.bundle.min.js");
        // rawgit is best effort, might not be reliable
        // Requirements::javascript("https://cdn.rawgit.com/RobinHerbots/Inputmask/$version/dist/min/jquery.inputmask.bundle.min.js");
        Requirements::javascript("https://cdn.jsdelivr.net/npm/inputmask@$version/dist/min/jquery.inputmask.bundle.min.js");
        Requirements::javascript('base/javascript/ModularBehaviour.js');
        Requirements::javascript('base/javascript/fields/InputMaskField.js');
        return parent::Field($properties);
    }
}
