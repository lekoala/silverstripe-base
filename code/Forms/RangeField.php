<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

/**
 *
 */
class RangeField extends TextField
{
    /**
     * Config array
     *
     * @var array
     */
    protected $config = [];

    /**
     * @config
     * @var array
     */
    private static $default_config = [
        'polyfill' => false,
    ];

    /**
     * @config
     * @var string
     */
    private static $version = '2.3.2';

    public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null)
    {
        parent::__construct($name, $title, $value, $maxLength, $form);
        $this->config = self::config()->default_config;
    }

    public function getInputType()
    {
        return 'range';
    }

    public function Type()
    {
        return 'range';
    }


    /**
     * Get a config key value
     *
     * @see https://flatpickr.js.org/options/
     * @param string $key
     * @return string
     */
    public function getConfig($key)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
    }

    /**
     * Set a config value
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    public function setConfig($key, $value)
    {
        if ($value !== null) {
            $this->config[$key] = $value;
        } else {
            unset($this->config[$key]);
        }
        return $this;
    }

    public function Field($properties = array())
    {
        $this->setAttribute('data-module', 'rangeslider');
        $this->setAttribute('data-config', json_encode($config));

        self::requirements();

        return parent::Field($properties);
    }

    public static function requirements()
    {
        $version = self::config()->version;
        Requirements::css("https://cdnjs.cloudflare.com/ajax/libs/rangeslider.js/$version/rangeslider.min.css");
        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/rangeslider.js/$version/rangeslider.min.js");
        Requirements::javascript('base/javascript/ModularBehaviour.js');
    }
}
