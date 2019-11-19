<?php

namespace LeKoala\Base\Dev\Tasks;

use SilverStripe\ORM\DB;
use SilverStripe\Assets\File;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\Director;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;

/**
 * Generates FileHash and moves files to .protected folder
 * Re-generates CMS thumbs on the second run and publish files
 *
 * @link https://gist.github.com/thezenmonkey/1b7846a01255e94c02906da6d121385a
 * @link https://forum.silverstripe.org/t/upgrade-from-3-to-4-1-assets-dont-work/184/7
 * @link https://docs.silverstripe.org/en/4/developer_guides/files/file_migration/
 */
class PublishAllFilesTask extends BuildTask
{
    private static $segment = 'PublishAllFilesTask';

    /**
     * @return AssetAdmin
     */
    public static function getAssetAdmin()
    {
        return AssetAdmin::singleton();
    }

    public function init()
    {
        set_time_limit(0);
        SubsiteHelper::disableFilter();
        $admin = self::getAssetAdmin();

        $originalDir = BASE_PATH . '/'. Director::publicDir() . '/assets/';

        $files = File::get();

        $this->message("Processing {$files->count()} files");

        $i = 0;
        foreach ($files as $file) {
            $i++;
            $name = $file->getFilename();

            if (!$name) {
                continue;
            }

            $originalName = $originalDir.$name;

            // Generate a file hash if not set
            if (!$file->getField('FileHash') && is_file($originalName)) {
                $hash = sha1_file($originalName);
                DB::query('UPDATE "File" SET "FileHash" = \''.$hash.'\' WHERE "ID" = \''.$file->ID.'\' LIMIT 1;');
                $targetDir = str_replace('./', '', BASE_PATH . '/' . Director::publicDir() . '/assets/.protected/'. dirname($name)
                    .'/'. substr($hash, 0, 10) . '/');
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                rename($originalDir . $name, $targetDir . basename($name));
                echo '<b style="color:red">'.$originalDir . $name .' > '. $targetDir . basename($name).'</b><br>';
            } else {
                // Will only apply to images
                $admin->generateThumbnails($file);
                // Publish
                try {
                    $file->copyVersionToStage('Stage', 'Live');
                    $this->message("Published $name", "created");
                } catch (Exception $ex) {
                    $this->message($ex->getMessage(), "error");
                }
            }

            $file->destroy();
        }
        $this->message("Processed $i files");
    }

    public function isEnabled()
    {
        return Director::isDev();
    }
}
