<?php
namespace LeKoala\Base\Forms;

use IntlDateFormatter;
use SilverStripe\i18n\i18n;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * @link https://chmln.github.io/flatpickr
 */
class FlatpickrField extends TextField
{
    use ConfigurableField;

    // Formats
    const DEFAULT_DATE_FORMAT = 'Y-m-d';
    const DEFAULT_TIME_FORMAT = 'H:i';
    const DEFAULT_DATETIME_FORMAT = 'Y-m-d H:i';
    const DEFAULT_ALT_DATE_FORMAT = 'l j F Y';
    const DEFAULT_ALT_TIME_FORMAT = 'H:i';
    const DEFAULT_ALT_DATETIME_FORMAT = 'l j F Y H:i';
    // Plugins
    const PLUGIN_CONFIRM_DATE = 'confirmDate/confirmDate';
    const PLUGIN_RANGE = 'rangePlugin';
    const PLUGIN_SCROLL = 'scrollPlugin';
    const PLUGIN_MIN_MAX_TIME = 'minMaxTimePlugin';
    const PLUGIN_WEEK_SELECT = 'weekSelect/weekSelect';
    const PLUGIN_MONTH_SELECT = 'monthSelect/monthSelect';
    const PLUGINS_WITH_CSS = [
        self::PLUGIN_CONFIRM_DATE,
        self::PLUGIN_MONTH_SELECT
    ];
    /**
     * @var bool
     */
    protected $html5 = true;

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Override date format. If empty will default to that used by the current locale.
     *
     * @var null
     */
    protected $datetimeFormat = null;

    /**
     * Disable description
     *
     * @var boolean
     */
    protected $disableDescription = false;

    /**
     * Custom timezone
     *
     * @var string
     */
    protected $timezone = null;

    /**
     * Array of plugins
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * @var string
     */
    protected $theme;

    /**
     * Id of the second element
     *
     * @var string
     */
    protected $range;

    /**
     * Add confirm box
     *
     * @var bool
     */
    protected $confirmDate;

    /**
     * @config
     * @var string
     */
    private static $version = '4.6.6';

    /**
     * @config
     * @link https://flatpickr.js.org/options/
     * @var array
     */
    private static $default_config = [
        'allowInput' => true, // Otherwise it will show as a readonly field
        'altInput' => true,
        'altInputClass' => 'text flatpickr-alt',
        'defaultDate' => '',
        'time_24hr' => true,
    ];

    public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null)
    {
        parent::__construct($name, $title, $value, $maxLength, $form);

        $this->config = self::config()->default_config;
        $this->setDatetimeFormat($this->convertDatetimeFormat(self::DEFAULT_ALT_DATE_FORMAT));
        $this->setAltFormat(self::DEFAULT_ALT_DATE_FORMAT);
    }

    /**
     * @param string $plugin
     * @return $this
     */
    public function addPlugin($plugin)
    {
        if (!isset($this->plugins[$plugin])) {
            $this->plugins[] = $plugin;
        }
        return $this;
    }

    /**
     * @param string $plugin
     * @return $this
     */
    public function removePlugin($plugin)
    {
        if (isset($this->plugins[$plugin])) {
            unset($this->plugins[$plugin]);
        }
        return $this;
    }

    /**
     * @return void
     */
    public function clearPlugins()
    {
        $this->plugins = [];
        return $this;
    }

    /**
     * Get the value of theme
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set the value of theme
     *
     * @param string $theme
     *
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Convert a datetime format from Flatpickr to CLDR
     *
     * This allow to display the right format in php
     *
     * @see https://flatpickr.js.org/formatting/
     * @param string $format
     * @return string
     */
    protected function convertDatetimeFormat($format)
    {
        return str_replace(
            ['F', 'l', 'j', 'd', 'H', 'i', 's'],
            ['MMMM', 'cccc', 'd', 'dd', 'HH', 'mm', 'ss'],
            $format
        );
    }

    public function Type()
    {
        return 'flatpickr';
    }

    public function extraClass()
    {
        return 'text ' . parent::extraClass();
    }

    public function getEnableTime()
    {
        return $this->getConfig('enableTime');
    }

    public function setEnableTime($value)
    {
        $this->setDatetimeFormat($this->convertDatetimeFormat(self::DEFAULT_ALT_DATETIME_FORMAT));
        $this->setAltFormat(self::DEFAULT_ALT_DATETIME_FORMAT);
        $this->setConfirmDate(true);
        return $this->setConfig('enableTime', $value);
    }

    public function getNoCalendar()
    {
        return $this->getConfig('noCalendar');
    }

    public function setNoCalendar($value)
    {
        $this->setDatetimeFormat($this->convertDatetimeFormat(self::DEFAULT_ALT_TIME_FORMAT));
        $this->setAltFormat(self::DEFAULT_ALT_TIME_FORMAT);
        return $this->setConfig('noCalendar', $value);
    }

    /**
     * Show the user a readable date (as per altFormat), but return something totally different to the server.
     *
     * @return string
     */
    public function getAltInput()
    {
        return $this->getConfig('altInput');
    }

    public function setAltInput($value)
    {
        return $this->setConfig('altInput', $value);
    }

    /**
     * Exactly the same as date format, but for the altInput field
     *
     * @return string
     */
    public function getAltFormat()
    {
        return $this->getConfig('altFormat');
    }

    /**
     * Please note that altFormat should match the format for the database
     *
     * @param string $value
     * @return $this
     */
    public function setAltFormat($value)
    {
        return $this->setConfig('altFormat', $value);
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

    public function getDateFormat()
    {
        return $this->getConfig('dateFormat');
    }

    public function setDateFormat($value)
    {
        return $this->setConfig('dateFormat', $value);
    }

    /**
     * Get id of the second element
     *
     * @return string
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * Set id of the second element
     *
     * eg: #Form_ItemEditForm_EndDate
     *
     * @param string $range Id of the second element
     * @param bool $confirm
     * @return $this
     */
    public function setRange($range, $confirm = true)
    {
        // enable this when it works properly with altInput
        // $this->addPlugin(self::PLUGIN_RANGE);
        $this->range = $range;
        if ($confirm) {
            $this->setConfirmDate(true);
        }
        return $this;
    }


    /**
     * Get add confirm box
     *
     * @return bool
     */
    public function getConfirmDate()
    {
        return $this->confirmDate;
    }

    /**
     * Set add confirm box
     *
     * @param bool $confirmDate Add confirm box
     *
     * @return $this
     */
    public function setConfirmDate($confirmDate)
    {
        $this->addPlugin(self::PLUGIN_CONFIRM_DATE);
        $this->confirmDate = $confirmDate;
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

    /**
     * @return bool
     */
    public function getHTML5()
    {
        return $this->html5;
    }


    /**
     * This is required (and ignored) because DBDate use this to scaffold the field
     *
     * @param boolean $bool
     * @return $this
     */
    public function setHTML5($bool)
    {
        $this->html5 = $bool;
        return $this;
    }


    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        if ($this->value && $timezone !== $this->timezone) {
            throw new \BadMethodCallException("Can't change timezone after setting a value");
        }

        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get date format in CLDR standard format
     *
     * This can be set explicitly. If not, this will be generated from the current locale
     * with the current date length.
     * @see http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Field-Symbol-Table
     */
    public function getDatetimeFormat()
    {
        if ($this->datetimeFormat) {
            return $this->datetimeFormat;
        }

        // Get from locale
        return $this->getFrontendFormatter()->getPattern();
    }

    /**
     * Set date format in CLDR standard format.
     * Only applicable with {@link setHTML5(false)}.
     *
     * @see http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Field-Symbol-Table
     * @param string $format
     * @return $this
     */
    public function setDatetimeFormat($format)
    {
        $this->datetimeFormat = $format;
        return $this;
    }

    /**
     * Get date formatter with the standard locale / date format
     *
     * @throws \LogicException
     * @return IntlDateFormatter
     */
    protected function getFrontendFormatter()
    {
        $formatter = IntlDateFormatter::create(
            $this->getLocale(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::MEDIUM,
            $this->getTimezone()
        );

        if ($this->datetimeFormat) {
            // Don't invoke getDatetimeFormat() directly to avoid infinite loop
            $ok = $formatter->setPattern($this->datetimeFormat);
            if (!$ok) {
                throw new InvalidArgumentException("Invalid date format {$this->datetimeFormat}");
            }
        } else {
            $formatter->setPattern(DBDatetime::ISO_DATETIME_NORMALISED);
        }
        return $formatter;
    }

    public function setDescription($description)
    {
        // Allows blocking scaffolded UI desc that has no uses
        if ($this->disableDescription) {
            return $this;
        }
        return parent::setDescription($description);
    }

    public function Field($properties = array())
    {
        // Set lang based on locale
        $lang = substr($this->getLocale(), 0, 2);
        if ($lang != 'en') {
            $this->setConfig('locale', $lang);
        }
        $this->setAttribute('data-module', 'flatpickr');
        $this->setAttribute('data-config', json_encode($this->config));

        if ($this->range) {
            $this->setAttribute('data-range', $this->range);
        }
        if ($this->confirmDate) {
            $this->setAttribute('data-confirm-date', true);
        }

        self::requirements($lang, $this->plugins, $this->theme);

        if ($this->readonly) {
            if ($this->getNoCalendar() && $this->getEnableTime()) {
                $this->setAttribute('placeholder', _t('FlatpickrField.NO_TIME_SELECTED', 'No time'));
            } else {
                $this->setAttribute('placeholder', _t('FlatpickrField.NO_DATE_SELECTED', 'No date'));
            }
        } else {
            $this->setAttribute('placeholder', _t('FlatpickrField.SELECT_A_DATE', 'Select a date...'));
        }

        return parent::Field($properties);
    }

    public static function requirements($lang = null, $plugins = [], $theme = null)
    {
        if ($lang === null) {
            $lang = substr(i18n::get_locale(), 0, 2);
        }
        $version = self::config()->version;
        $cdnBase = "https://cdnjs.cloudflare.com/ajax/libs/flatpickr/$version";
        // $cdnBase = "https://cdn.jsdelivr.net/npm/flatpickr@$version/dist";
        Requirements::css("$cdnBase/flatpickr.min.css");
        Requirements::javascript("$cdnBase/flatpickr.js");
        if ($lang != 'en') {
            Requirements::javascript("$cdnBase/l10n/$lang.js");
        }
        foreach ($plugins as $plugin) {
            Requirements::javascript("$cdnBase/plugins/$plugin.js");
            if (isset(self::PLUGINS_WITH_CSS[$plugin])) {
                Requirements::css("h$cdnBase/plugins/$plugin.css");
            }
        }
        if ($theme) {
            Requirements::css("$cdnBase/themes/$theme.css");
        }

        // Order matters for hooks ! Otherwise ready may fire before hooks are defined!
        Requirements::javascript('base/javascript/fields/FlatpickrField.js');
        Requirements::javascript('base/javascript/ModularBehaviour.js');
    }

    /**
     * Get disable description
     *
     * @return  boolean
     */
    public function getDisableDescription()
    {
        return $this->disableDescription;
    }

    /**
     * Set disable description
     *
     * @param boolean $disableDescription
     *
     * @return $this
     */
    public function setDisableDescription($disableDescription)
    {
        $this->disableDescription = $disableDescription;
        return $this;
    }

    public function setReadonly($readonly)
    {
        $this->setConfig('clickOpens', !$readonly);
        $this->setConfig('allowInput', !$readonly);
        return parent::setReadonly($readonly);
    }

    /**
     * Returns a read-only version of this field.
     *
     * @return FormField
     */
    public function performReadonlyTransformation()
    {
        $clone = $this->castedCopy(self::class);
        $clone->replaceConfig($this->config);
        $clone->setReadonly(true);
        return $clone;
    }

    /**
     * Set typical options for a DateTime field
     * @return $this
     */
    public function setDateTimeOptions()
    {
        $this->setEnableTime(true);
        $this->setDisableDescription(true);
        return $this;
    }

    /**
     * Set typical options for a Time field
     * @return $this
     */
    public function setTimeOptions()
    {
        $this->setEnableTime(true);
        $this->setNoCalendar(true);
        return $this;
    }
}
