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

    public function setConfig($key, $value)
    {
        if ($value) {
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
        return $this->getConfig('dataformat');
    }

    public function setDataFormat($value)
    {
        return $this->setConfig('dataformat', $value);
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

    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $attributes['lang'] = i18n::convert_rfc1766($this->getLocale());

        return $attributes;
    }

    public function Field($properties = array())
    {
        foreach ($this->config as $k => $v) {
            $this->setAttribute('data-inputmask-' . $k, $v);
        }
        Requirements::javascript('https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/jquery.inputmask.bundle.min.js');
        Requirements::javascript('base/javascript/fields/InputMaskField.js');
        return parent::Field($properties);
    }
}
