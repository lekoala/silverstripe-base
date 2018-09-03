<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

/**
 *
 */
class RangeField extends TextField
{
    use ConfigurableField;

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
        $this->mergeDefaultConfig();
    }

    public function getInputType()
    {
        return 'range';
    }

    public function Type()
    {
        return 'range';
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
