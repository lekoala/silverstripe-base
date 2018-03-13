<?php
namespace LeKoala\Base\Dev;

use SilverStripe\Core\Kernel;
use LeKoala\Base\Blocks\Block;
use SilverStripe\View\SSViewer;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Control\Director;
use SilverStripe\View\ThemeManifest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\DevBuildController;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Core\Manifest\ClassLoader;
use LeKoala\Base\Theme\KnowsThemeDir;

/**
 * Block Create Task
 *
 * @author lekoala
 */
class BlocksCreateTask extends BuildTask
{
    use KnowsThemeDir;

    protected $title = "Create Blocks";
    protected $description = 'Create block classes based on your templates.';
    private static $segment = 'BlocksCreateTask';

    public function init()
    {
        $themeBlocksPath = Director::baseFolder() . DIRECTORY_SEPARATOR . $this->getThemeDir() . '/templates/Blocks';
        $mysiteBlocksPath = Director::baseFolder() . DIRECTORY_SEPARATOR . project() . '/templates/Blocks';

        $mysite = Director::baseFolder() . '/mysite/code/Blocks';
        if (!is_dir($mysite)) {
            mkdir($mysite);
        }

        $classes = Block::listTemplates();

        $files = [];
        $files = array_merge($files, glob($themeBlocksPath . '/*.ss'));
        $files = array_merge($files, glob($mysiteBlocksPath . '/*.ss'));

        if (empty($files)) {
            $this->message("No blocks found");
            $this->message("Please make sure you have blocks in $themeBlocksPath or in $mysiteBlocksPath");
        }

        $created = false;
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (isset($classes[$name])) {
                $this->message("Skip block $name");
                continue;
            }
            $this->message("Creating block $name", "created");

            $filename = $mysite . DIRECTORY_SEPARATOR . $name . '.php';

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

        if ($created) {
            $this->regenerateClassManifest();
        }
    }

}
