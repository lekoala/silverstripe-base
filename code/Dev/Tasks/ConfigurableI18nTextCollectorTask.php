<?php

namespace LeKoala\Base\Dev\Tasks;

use SilverStripe\i18n\i18n;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\Tasks\i18nTextCollectorTask;
use SilverStripe\i18n\TextCollection\i18nTextCollector;
use LeKoala\Base\Dev\TextCollector;

/**
 * A better task for collecting text
 *
 * Use our TextCollector under the hood that actually works...
 */
class ConfigurableI18nTextCollectorTask extends BuildTask
{
    protected static $ignored_once = false;

    private static $segment = 'i18nTextCollectorTask';

    protected $title = "i18n Textcollector Task";

    protected $description = "
		Traverses through files in order to collect the 'entity master tables'
		stored in each module.
	";

    /**
     * This is the main method to build the master string tables with the original strings.
     * It will search for existent modules that use the i18n feature, parse the _t() calls
     * and write the resultant files in the lang folder of each module.
     *
     * @uses DataObject->collectI18nStatics()
     *
     * @param HTTPRequest $request
     */
    public function init(HTTPRequest $request)
    {
        $this->increaseTimeLimitTo();

        $this->addOption("locale", "Locale to use", substr(i18n::get_locale(), 0, 2));
        $this->addOption("merge", "Merge with previous translations", true);
        $this->addOption("clear_unused", "Remove keys that are not used anymore", false);
        $this->addOption("debug", "Show debug messages and prevent write", false);
        $this->addOption("modules", "Comma separated list of modules", project());

        $options = $this->askOptions();

        $locale = $options['locale'];
        $merge = $options['merge'];
        $modules = $options['modules'];
        $clearUnused = $options['clear_unused'];
        $debug = $options['debug'];

        if ($locale) {
            $this->message("Proceeding with locale $locale for modules $modules");
            $collector = TextCollector::create($locale);
            $restrictModules = explode(',', $modules);
            $collector->setRestrictToModules($restrictModules);
            $collector->setMergeWithExisting($merge);
            $collector->setClearUnused($clearUnused);
            $collector->setDebug($debug);
            $collector->run($restrictModules, $merge);
        }
    }

    public function isEnabled()
    {
        if (self::$ignored_once) {
            return true;
        }
        // Basically hide the previous task
        self::$ignored_once = true;
        return false;
    }
}
