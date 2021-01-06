<?php

namespace LeKoala\Base\Dev;

use Exception;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\BuildTask as DefaultBuildTask;

/**
 * This is an improved BuildTask
 *
 * - Cleaner looks
 * - Web UI for options
 * - Messaging
 * - Common imports (Env, Request)
 * - Utils
 *
 * See BuildTask::init and commented code to get started!
 */
abstract class BuildTask extends DefaultBuildTask
{
    use BuildTaskTools;

    /**
     * Called by TaskRunner::runTask
     *
     * @param HTTPRequest $request
     * @return void
     */
    public function run($request)
    {
        // Clean output from task runner
        ob_get_clean();

        $this->outputHeader();
        $this->request = $request;

        // We need to catch exception ourselves due to a subsite bug
        // @link https://github.com/silverstripe/silverstripe-subsites/issues/377
        try {
            $this->init($request);
        } catch (Exception $ex) {
            $debug = new BetterDebugView;
            $debug->writeException($ex);
        }

        $this->outputFooter();
    }

    protected function init()
    {
        // Call you own code here in your subclasses
        // $this->addOption("my_bool_option", "My Bool Option", false);
        // $this->addOption("my_list_option", "My List Option", null, $list);
        // $options = $this->askOptions();

        // $my_bool_option = $options['my_bool_option'];
        // $my_list_option = $options['my_list_option'];

        // if ($my_bool_option) {
        //     $this->message("Totally true");
        // } else {
        //     $this->message("Not true");
        // }
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if ($this->title) {
            return $this->title;
        }
        $class = ClassHelper::getClassWithoutNamespace(static::class);
        $re = '/(?#! splitCamelCase Rev:20140412)
    # Split camelCase "words". Two global alternatives. Either g1of2:
      (?<=[a-z])      # Position is after a lowercase,
      (?=[A-Z])       # and before an uppercase letter.
    | (?<=[A-Z])      # Or g2of2; Position is after uppercase,
      (?=[A-Z][a-z])  # and before upper-then-lower case.
    /x';
        $parts = preg_split($re, $class);
        array_pop($parts);
        return implode(" ", $parts);
    }

    protected function outputHeader()
    {
        $taskTitle = $this->getTitle();
        if (Director::is_cli()) {
            // Nothing in CLI
        } else {
            $html = "<!DOCTYPE html><html><head><title>$taskTitle</title>";
            $html .= '<link rel="stylesheet" type="text/css" href="/resources/base/css/buildtask.css" />';
            $html .= '</head><body><div class="info header"><h1>Running Task ' . $taskTitle . '</h1></div><div class="build">';
            echo $html;
        }
    }

    protected function outputFooter()
    {
        if (Director::is_cli()) {
            // Nothing in CLI
        } else {
            $html = "</div></body>";
            echo $html;
        }
    }

    /**
     * @return Monolog\Logger
     */
    public function getLogger()
    {
        return Injector::inst()->get(LoggerInterface::class)->withName('BuildTask');
    }
}
