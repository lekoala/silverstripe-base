<?php

namespace LeKoala\Base\Dev\Tasks;

use SilverStripe\Assets\Image;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Assets\Storage\Sha1FileHashingService;
use SilverStripe\ORM\DB;

/**
 * In case images are resized by an external utility
 * file hash is not valid anymore
 *
 * We need a way to rehash them
 */
class RehashImagesTask extends BuildTask
{
    protected $title = "Rehash Images";
    private static $segment = 'RehashImagesTask';

    public function init()
    {
        $request = $this->getRequest();

        $this->addOption("go", "Set this to 1 to proceed", 0);

        $options = $this->askOptions();

        $go = $options['go'];

        $service = new Sha1FileHashingService();

        $images = Image::get();
        $tofix = $todelete = 0;
        foreach ($images as $image) {
            $hash = $image->getHash();

            $stream  = $image->getStream();
            if (!$stream) {
                $filename = $image->getFilename();
                $location = ASSETS_PATH . '/' . $filename;
                if (!is_file($location)) {
                    $todelete++;
                    if ($go) {
                        $this->message("Deleted file " . $image->ID . " since the file was not found");
                        $image->delete();
                    } else {
                        $this->message("Could not read file at $location. It would be deleted by this task.", "bad");
                    }
                    continue;
                }
                $stream = fopen($location, 'rb');
            }

            $fullhash = $service->computeFromStream($stream);

            if ($hash != $fullhash) {
                $tofix++;
                if ($go) {
                    DB::query("UPDATE File SET FileHash = '" . $fullhash . "' WHERE ID = " . $image->ID);
                    DB::query("UPDATE File_Live SET FileHash = '" . $fullhash . "' WHERE ID = " . $image->ID);
                    $this->message($image->ID . " has been fixed");
                } else {
                    $this->message($image->ID . " hash mismatch : $hash vs $fullhash. This task can fix this hash.");
                }
            }
        }

        if (!$tofix && !$todelete) {
            $this->message("All files are good", "good");
        }
    }
}
