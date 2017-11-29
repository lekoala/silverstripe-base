<?php
namespace LeKoala\Base\Forms;

use SilverStripe\i18n\i18n;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

/**
 * @link https://chmln.github.io/flatpickr
 */
class FlatpickrField extends TextField
{
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

    private static $default_config = [
        'altInput' => true,
    ];

    public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null)
    {
        parent::__construct($name, $title, $value, $maxLength, $form);

        $this->config = self::config()->default_config;
    }

    public function Type()
    {
        return 'flatpickr';
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

    public function getEnableTime()
    {
        return $this->getConfig('enableTime');
    }

    public function setEnableTime($value)
    {
        return $this->setConfig('enableTime', $value);
    }

    public function getAltInput()
    {
        return $this->getConfig('altInput');
    }

    public function setAltInput($value)
    {
        return $this->setConfig('altInput', $value);
    }

    public function getMinDate()
    {
        return $this->getConfig('minDate');
    }

    public function setMinDate($value)
    {
        return $this->setConfig('minDate', $value);
    }

    public function getMaxDate()
    {
        return $this->getConfig('maxDate');
    }

    public function setMaxDate($value)
    {
        return $this->setConfig('maxDate', $value);
    }

    public function getDefaultDate()
    {
        return $this->getConfig('defaultDate');
    }

    public function setDefaultDate($value)
    {
        return $this->setConfig('defaultDate', $value);
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

    /**
     * This is required (and ignored) because DBDate use this to scaffold the field
     *
     * @param boolean $bool
     * @return $this
     */
    public function setHTML5($bool)
    {
        return $this;
    }


    public function Field($properties = array())
    {
        // Set lang based on locale
        $lang = substr($this->getLocale(), 0, 2);
        if ($lang != 'en') {
            $this->setConfig('locale', $lang);
        }

        $this->setAttribute('data-flatpickr', json_encode($this->config));

        Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.1.3/flatpickr.min.css');
        Requirements::javascript('https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.1.3/flatpickr.js');
        if ($lang != 'en') {
            Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.1.3/l10n/$lang.js");
        }
        Requirements::javascript('base/javascript/FlatpickrField.js');
        return parent::Field($properties);
    }
}
