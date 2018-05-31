<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DataObject;

class BaseFileExtension extends DataExtension
{
    use Configurable;

    /**
    * @config
    * @var string
    */
    private static $auto_clear_threshold = null;

    private static $db = [
        "IsTemporary" => "Boolean",
    ];
    private static $has_one = [
        "Record" => DataObject::class,
    ];

    public function onBeforeWrite()
    {
        if(!$this->RecordID) {
            $this->RecordClass = null;
        }
    }

    /**
     * Clear temp folder that should not contain any file other than temporary
     *
     * @param boolean $doDelete
     * @param string $threshold
     * @return File[] List of files removed
     */
    public static function clearTemporaryUploads($doDelete = false, $threshold = null)
    {
        $tempFiles = File::get()->filter('IsTemporary', true);

        if ($threshold === null) {
            $threshold = self::config()->auto_clear_threshold;
        }
        if (!$threshold) {
            if (Director::isDev()) {
                $threshold = '-10 minutes';
            } else {
                $threshold = '-1 day';
            }
        }
        if (is_int($threshold)) {
            $thresholdTime = $threshold;
        } else {
            $thresholdTime = strtotime($threshold);
        }
        $filesDeleted = [];
        foreach ($tempFiles as $tempFile) {
            $createdTime = strtotime($tempFile->Created);
            if ($createdTime < $thresholdTime) {
                $filesDeleted[] = $tempFile;
                if ($doDelete) {
                    $tempFile->deleteAll();
                }
            }
        }
        return $filesDeleted;
    }
}
