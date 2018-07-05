<?php
namespace LeKoala\Base\Dev\Tasks;

use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Director;
use LeKoala\Base\Helpers\FileHelper;

/**
 */
class ClearCacheFolderTask extends BuildTask
{
    protected $description = 'Clear silverstripe-cache folder.';
    private static $segment = 'ClearCacheFolderTask';

    public function init()
    {
        $folder = Director::baseFolder() . '/silverstripe-cache';
        if (!is_dir($folder)) {
            throw new Exception("silverstripe-cache folder does not exist in root");
        }

        FileHelper::rmDir($folder);
        mkdir($folder, 0755);
    }
}
