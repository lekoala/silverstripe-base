<?php

namespace LeKoala\Base\Dev;

use SilverStripe\ORM\ArrayLib;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ModuleLoader;

/**
 * Makes your life easier with build tasks
 */
trait BuildTaskTools
{
    /**
     * @var SilverStripe\Control\HTTPRequest
     */
    protected $request;

    /**
     * @var array
     */
    protected $options = [];

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
     * All modules
     *
     * @return array
     */
    protected function getModules()
    {
        return ArrayLib::valuekey(array_keys(ModuleLoader::inst()->getManifest()->getModules()));
    }

    /**
     * Get the request (and keep your imports clean :-) )
     *
     * @return HTTPRequest
     */
    protected function getRequest()
    {
        if (!$this->request) {
            die('Make sure to call $this->request = $request in your own class');
        }
        return $this->request;
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
        echo '<form action="" method="post"><fieldset>';
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
        echo '</fieldset><br/><input type="submit" />';
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
}
