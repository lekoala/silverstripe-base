<?php
namespace LeKoala\Base\Dev\Tasks;

use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Versioned\Versioned;

/**
 */
class SitePublisherTask extends BuildTask
{
    protected $title = "Site Publisher";
    protected $description = 'Publish the whole site in one click.';
    private static $segment = 'SitePublisherTask';

    public function init()
    {
        $request = $this->getRequest();

        $this->addOption("classes", "Classes to publish (comma separated)", 'Page');
        $this->addOption("go", "Set this to 1 to proceed", 0);

        $options = $this->askOptions();

        $classes = $options['classes'];
        $go = $options['go'];

        $cbSave = function ($List, $publish = false) {
            foreach ($List as $Item) {
                $this->message('Saving item "' . $Item->getTitle() . '"');
                if ($publish) {
                    $Item->publishRecursive();
                } else {
                    $Item->write();
                }
            }
        };

        $classesToPublish = explode(',', $classes);

        if ($go) {
            foreach ($classesToPublish as $class) {
                $this->message("Publishing class $class");

                $List = $class::get();
                $singl = $class::singleton();
                $fluent = $singl->has_extension("\\TractorCow\\Fluent\\Extension\\FluentExtension");

                $publish = $singl->has_extension(Versioned::class);

                // With fluent we need to change state when saving
                if ($fluent) {
                    $state = \TractorCow\Fluent\State\FluentState::singleton();
                    $allLocales = \TractorCow\Fluent\Model\Locale::get();
                    foreach ($allLocales as $locale) {
                        $this->message('Publishing with locale ' . $locale->Locale, "info");
                        $state->withState(function ($state) use ($locale, $List, $cbSave, $publish) {
                            $state->setLocale($locale->Locale);
                            $cbSave($List, $publish);
                        });
                    }
                } else {
                    $cbSave($List, $publish);
                }
            }
        } else {
            foreach ($classesToPublish as $class) {
                $this->message("Would publish " . $class::get()->count() . " items for class " . $class);
            }
        }
    }
}
