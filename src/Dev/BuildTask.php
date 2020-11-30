<?php

namespace LeKoala\Base\Dev;

use Exception;
use SilverStripe\ORM\DB;
use Psr\Log\LoggerInterface;
use SilverStripe\Dev\Backtrace;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\Dev\BuildTask as DefaultBuildTask;
use SilverStripe\Logging\DetailedErrorFormatter;

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
    // Message constants
    const INFO = 'info';
    const BAD = 'bad';
    const SUCCESS = 'success';
    const CREATED = 'created';
    const CHANGED = 'changed';
    const REPAIRED = 'repaired';
    const OBSOLETE = 'obsolete';
    const DELETE = 'delete';
    const NOTICE = 'notice';
    const ERROR = 'error';

    /**
     * @var SilverStripe\Control\HTTPRequest
     */
    protected $request;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Called by TaskRunner::runTask
     *
     * @param [type] $request
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
     * Increase time limit
     *
     * @return void
     */
    protected function increaseTimeLimitTo($timeLimit = null)
    {
        Environment::increaseTimeLimitTo($timeLimit);
        if (!$timeLimit) {
            $this->message("Time limit is disabled", "info");
        } else {
            $this->message("Time limit has been set to $timeLimit seconds", "info");
        }
    }

    /**
     * Rebuild the class manifest
     *
     * @return void
     */
    protected function regenerateClassManifest()
    {
        ClassLoader::inst()->getManifest()->regenerate(false);
        $this->message("The class manifest has been rebuilt", "created");
    }

    /**
     * Get the request (and keep your imports clean :-) )
     *
     * @return HTTPRequest
     */
    protected function getRequest()
    {
        if (!$this->request) {
            die('Make sure to call parent::run($request) in your own class');
        }
        return $this->request;
    }

    /**
     * All dataobjects
     *
     * @return array
     */
    protected function getValidDataObjects()
    {
        $list = ClassInfo::getValidSubClasses(DataObject::class);
        array_shift($list);
        return $list;
    }

    /**
     * Add options (to be called later with askOptions)
     *
     * @param string $key
     * @param string $title
     * @param mixed $default Default value. Input type will be based on this (bool => checkbox, etc)
     * @param array|Map $list An array of value for a dropdown
     * @return void
     */
    protected function addOption($key, $title, $default = '', $list = null)
    {
        // Handle maps
        if (is_object($list) && method_exists($list, 'toArray')) {
            $list = $list->toArray();
        }
        $opt = [
            'key' => $key,
            'title' => $title,
            'default' => $default,
            'list' => $list,
        ];
        $this->options[] = $opt;

        return $opt;
    }

    /**
     * Display a form with options
     *
     * Options are added through addOption method
     *
     * @return array Array with key => value corresponding to options asked
     */
    protected function askOptions()
    {
        $values = [];
        $request = $this->getRequest();
        echo '<form action="" method="post">';
        foreach ($this->options as $opt) {
            $val = $request->requestVar($opt['key']);
            if ($val === null) {
                $val = $opt['default'];
            }

            $values[$opt['key']] = $val;

            if ($opt['list']) {
                $input = '<select name="' . $opt['key'] . '">';
                $input .= '<option></option>';
                foreach ($opt['list'] as $k => $v) {
                    $selected = '';
                    if ($k == $val) {
                        $selected = ' selected="selected"';
                    }
                    $input .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
                }
                $input .= '</select>';
            } else {
                $type = 'text';
                $input = null;
                if (isset($opt['default'])) {
                    if (is_bool($opt['default'])) {
                        $type = 'checkbox';
                        $checked = $val ? ' checked="checked"' : '';
                        $input = '<input type="hidden" name="' . $opt['key'] . '" value="0" />';
                        $input .= '<input type="' . $type . '" name="' . $opt['key'] . '" value="1"' . $checked . ' />';
                    } else {
                        if (is_int($opt['default'])) {
                            $type = 'numeric';
                        }
                    }
                }
                if (!$input) {
                    $input = '<input type="' . $type . '" name="' . $opt['key'] . '" value="' . $val . '" />';
                }
            }
            echo '<div class="field">';
            echo '<label> ' . $opt['title'] . ' ' . $input . '</label>';
            echo '</div>';
            echo '<br/>';
        }
        echo '<input type="submit" />';
        echo '</form>';
        echo '<hr/ >';
        return $values;
    }

    protected function message($message, $type = 'default')
    {
        if (Director::is_cli()) {
            $cli_map = [
                'repaired' => '>',
                'success' => '✓',
                'created' => '+',
                'changed' => '+',
                'bad' => '-',
                'obsolete' => '-',
                'deleted' => '-',
                'notice' => '!',
                'error' => '-',
            ];

            $message = strip_tags($message);
            if (isset($cli_map[$type])) {
                $message = $cli_map[$type] . ' ' . $message;
            }
            if (!is_string($message)) {
                $message = json_encode($message);
            }
            echo "  $message\n";
        } else {
            $web_map = [
                'info' => 'blue',
                'repaired' => 'blue',
                'success' => 'green',
                'created' => 'green',
                'changed' => 'green',
                'obsolete' => 'red',
                'notice' => 'orange',
                'deleted' => 'red',
                'bad' => 'red',
                'error' => 'red',
            ];
            $color = '#000000';
            if (isset($web_map[$type])) {
                $color = $web_map[$type];
            }
            if (!is_string($message)) {
                $message = print_r($message, true);
                echo "<pre style=\"color:$color\">$message</pre>";
            } else {
                echo "<div style=\"color:$color\">$message</div>";
            }
        }
    }

    protected function isDev()
    {
        return Director::isDev();
    }

    protected function isLive()
    {
        return Director::isLive();
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return Injector::inst()->get(LoggerInterface::class)->withName('BuildTask');
    }
}
