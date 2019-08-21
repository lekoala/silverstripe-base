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
        $create = $_GET['create'] ?? false;
        if (!is_dir($folder)) {
            if ($create) {
                mkdir($folder, 0755);
            } else {
                throw new Exception("silverstripe-cache folder does not exist in root");
            }
        }

        $result = FileHelper::rmDir($folder);
        if ($result) {
            $this->message("Removed $folder");
        } else {
            $this->message("Failed to remove $folder", "error");
        }
        $result = mkdir($folder, 0755);
        if ($result) {
            $this->message("A new folder has been created at $folder");
        } else {
            $this->message("Failed to create a new folder at $folder", "error");
        }
    }
}
