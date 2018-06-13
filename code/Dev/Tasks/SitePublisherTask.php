<?php
namespace LeKoala\Base\Dev\Tasks;

use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;

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

        $this->addOption("other_classes", "Write other classes (comma separated)");
        $this->addOption("go", "Set this to 1 to proceed", 0);

        $options = $this->askOptions();

        $other_classes = $options['other_classes'];
        $go = $options['go'];

        $singl = \Page::singleton();
        $fluent = $singl->has_extension("\\TractorCow\\Fluent\\Extension\\FluentExtension");
        $Pages = \Page::get();

        $cbPublish = function ($Pages) {
            foreach ($Pages as $Page) {
                $this->message('Publishing page "' . $Page->Title . '"');
                $Page->publishRecursive();
            }
        };
        $cbSave = function ($List) {
            foreach ($List as $Item) {
                $this->message('Saving item "' . $Item->Title . '"');
                $Item->write();
            }
        };

        if ($go) {
            if ($fluent) {
                $state = \TractorCow\Fluent\State\FluentState::singleton();
                $allLocales = \TractorCow\Fluent\Model\Locale::get();
                foreach ($allLocales as $locale) {
                    $this->message('Publishing with locale ' . $locale->Locale);
                    $state->withState(function ($state) use ($locale, $Pages, $cbPublish) {
                        $state->setLocale($locale->Locale);
                        $cbPublish($Pages);
                    });
                }
            } else {
                $cbPublish($Pages);
            }

            if ($other_classes) {
                $arr = explode(',', $other_classes);

                foreach ($arr as $class) {
                    $this->message("Publishing class $class");

                    $List = $class::get();

                    if ($fluent) {
                        $state = \TractorCow\Fluent\State\FluentState::singleton();
                        $allLocales = \TractorCow\Fluent\Model\Locale::get();
                        foreach ($allLocales as $locale) {
                            $this->message('Publishing with locale ' . $locale->Locale);
                            $state->withState(function ($state) use ($locale, $List, $cbSave) {
                                $state->setLocale($locale->Locale);
                                $cbSave($List);
                            });
                        }
                    } else {
                        $cbSave($List);
                    }
                }
            }
        }
    }
}
