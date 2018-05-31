<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Assets\ImageBackendFactory;
use SilverStripe\Core\Injector\InjectionCreator;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;

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
        // Record is already used by versioned extensions
        // ChangeSetItem already uses Object convention so use it
        "Object" => DataObject::class,
    ];

    public function onBeforeWrite()
    {
        if (!$this->owner->ObjectID) {
            $this->owner->ObjectClass = null;
        }
    }

    public function onAfterWrite()
    {
        $this->generateDefaultThumbnails();
    }

    public function generateDefaultThumbnails()
    {
        if (!$this->owner->getIsImage()) {
            return;
        }
        $assetAdmin = AssetAdmin::singleton();
        $creator = new InjectionCreator();
        Injector::inst()->registerService(
            $creator,
            ImageBackendFactory::class
        );
        $assetAdmin->generateThumbnails($this->owner, true);
    }

    public static function ensureNullForEmptyRecordRelation()
    {
        DB::query("UPDATE File SET ObjectClass = null WHERE ObjectID = 0 AND ObjectClass IS NOT NULL");
        DB::query("UPDATE File_Live SET ObjectClass = null WHERE ObjectID = 0 AND ObjectClass IS NOT NULL");
        DB::query("UPDATE File_Versions SET ObjectClass = null WHERE ObjectID = 0 AND ObjectClass IS NOT NULL");
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
