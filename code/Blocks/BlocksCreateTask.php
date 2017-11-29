<?php
namespace LeKoala\Base\Dev;

use SilverStripe\Core\Kernel;
use SilverStripe\Dev\BuildTask;
use SilverStripe\View\SSViewer;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Control\Director;
use SilverStripe\View\ThemeManifest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\ThemeResourceLoader;
use LeKoala\Base\Blocks\Block;

/**
 * Block Create Task
 *
 * @author lekoala
 */
class BlocksCreateTask extends BuildTask
{

    protected $title = "Create Blocks";
    protected $description = 'Create block classes based on your templates.';
    private static $segment = 'BlocksCreateTask';

    public function run($request)
    {
        $theme = Director::baseFolder() . \DIRECTORY_SEPARATOR . $this->getThemeDir() . '/templates/Blocks';
        $mysite = Director::baseFolder() . '/mysite/code/Blocks';
        if(!is_dir($mysite)) {
            mkdir($mysite);
        }

        $classes =Block::listTemplates();
        $files = glob($theme . '/*.ss');

        $created = false;
        foreach($files as $file) {
            $name = pathinfo($file, \PATHINFO_FILENAME);
            if(isset($classes[$name])) {
                $this->message("Skip block $name");
                continue;
            }
            $this->message("Creating block $name","created");

            $filename = $mysite . \DIRECTORY_SEPARATOR . $name  . '.php';

            $data = <<<PHP
<?php
use SilverStripe\Forms\FieldList;
use LeKoala\Base\Blocks\BaseBlock;

class $name extends BaseBlock
{
    public function updateFields(FieldList \$fields)
    {
    }

    public function Collection()
    {
        return false;
    }

    public function SharedCollection()
    {
        return false;
    }
}
PHP;
            \file_put_contents($filename, $data);

            $created = true;
        }

        if($created) {
            $this->message("Make sure to run a dev/build to refresh the class manifest", "notice");
        }
    }

        /**
     * Get current theme dir
     *
     * @return string
     */
    public function getThemeDir()
    {
        $themes = SSViewer::get_themes();
        if ($themes) {
            $mainTheme = array_shift($themes);
            return 'themes/' . $mainTheme;
        }
        return '';
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
            if(isset($cli_map[$type])) {
                $message = $cli_map[$type] .' ' . $message;
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
            if(isset($web_map[$type])) {
                $color = $web_map[$type];
            }
            echo "<div style=\"color:$color\">$message</div>";
        }
    }

}
