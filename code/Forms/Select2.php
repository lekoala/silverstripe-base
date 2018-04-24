<?php
namespace LeKoala\Base\Forms;

use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;

trait Select2
{

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Multiple values
     *
     * @var boolean
     */
    protected $multiple = false;

    /**
     * Callback to create tags
     *
     * @var Callable
     */
    protected $onNewTag = null;

    /**
     * Config array
     *
     * @var array
     */
    protected $config = [];

    public function Type()
    {
        return 'select2';
    }

    public function extraClass()
    {
        return 'select no-chosen ' . parent::extraClass();
    }

    public function getConfig($key)
    {
        if (isset($this->config[$key])) {
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

    public function getTags()
    {
        return $this->getConfig('tags');
    }

    public function setTags($value)
    {
        return $this->setConfig('tags', $value);
    }

    public function getPlaceholder()
    {
        return $this->getConfig('placeholder');
    }

    public function setPlaceholder($value)
    {
        return $this->setConfig('placeholder', $value);
    }

    public function getAllowClear()
    {
        return $this->getConfig('allowClear');
    }

    public function setAllowClear($value)
    {
        return $this->setConfig('allowClear', $value);
    }

    public function getTokenSeparators()
    {
        return $this->getConfig('tokenSeparators');
    }

    public function setTokenSeparator($value)
    {
        return $this->setConfig('tokenSeparators', $value);
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
     * @return Callable
     */
    public function getOnNewTag()
    {
        return $this->onNewTag;
    }

    /**
     * The callback should return the new id
     *
     * @param Callable $locale
     * @return $this
     */
    public function setOnNewTag($callback)
    {
        $this->onNewTag = $callback;
        return $this;
    }

    public function Field($properties = array())
    {
        // Set lang based on locale
        $lang = substr($this->getLocale(), 0, 2);
        if ($lang != 'en') {
            $this->setConfig('language', $lang);
        }

        // Set RTL
        $dir = i18n::get_script_direction($this->getLocale());
        if ($dir == 'rtl') {
            $this->setConfig('dir', $dir);
        }

        $config = $this->config;

        // Do not use select2 because it is reserved
        $this->setAttribute('data-config', json_encode($config));

        Requirements::css('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css');
        Requirements::css('base/css/Select2Field.css');
        Requirements::javascript('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.js');
        if ($lang != 'en') {
            Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/i18n/$lang.js");
        }
        Requirements::javascript('base/javascript/fields/Select2Field.js');
        return parent::Field($properties);
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // Tags can be created on the fly and cannot be validated
        if ($this->getTags()) {
            return true;
        }

        return parent::validate($validator);
    }

}
