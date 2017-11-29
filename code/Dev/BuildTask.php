<?php
namespace LeKoala\Base\Dev;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask as DefaultBuildTask;

abstract class BuildTask extends DefaultBuildTask
{
    protected $request;
    protected $options = [];

    public function run($request)
    {
        $this->request = $request;
    }

    protected function getRequest()
    {
        if (!$this->request) {
            die('Make sure to call parent::run($request)');
        }
        return $this->request;
    }

    protected function getValidDataObjects()
    {
        $list = ClassInfo::getValidSubClasses(DataObject::class);
        \array_shift($list);
        return $list;
    }

    protected function addOption($key, $title, $default = '', $list = null)
    {
        $opt = [
            'key' => $key,
            'title' => $title,
            'default' => $default,
            'list' => $list,
        ];
        $this->options[] = $opt;

        return $opt;
    }

    protected function askOptions()
    {
        $values = [];
        $request = $this->getRequest();
        echo '<form>';
        foreach ($this->options as $opt) {
            $val = $request->getVar($opt['key']);
            if (!$val) {
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
                $input = '<input name="' . $opt['key'] . '" value="' . $val . '" />';
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

    protected function message($message, $type = 'info')
    {
        if (Director::is_cli()) {
            $cli_map = [
                'created' => '+',
                'changed' => '+',
                'repaired' => '+',
                'obsolete' => '-',
                'deleted' => '-',
                'notice' => '-',
                'error' => '-',
            ];

            $message = strip_tags($message);
            if (isset($cli_map[$type])) {
                $message = $cli_map[$type] . ' ' . $message;
            }
            echo "  $message\n";
        } else {
            $web_map = [
                'created' => 'green',
                'changed' => 'green',
                'repaired' => 'green',
                'obsolete' => 'red',
                'deleted' => 'red',
                'notice' => 'orange',
                'error' => 'red',
            ];
            $color = '#000000';
            if (isset($web_map[$type])) {
                $color = $web_map[$type];
            }
            echo "<div style=\"color:$color\">$message</div>";
        }
    }
}
