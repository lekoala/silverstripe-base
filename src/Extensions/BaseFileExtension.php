<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Assets\Image_Backend;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Assets\ImageBackendFactory;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Injector\InjectionCreator;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;

/**
 * Improved File usage
 *
 * - Track temporary files (and auto clean them)
 * - Allow polymorphic association to a dedicated record (allows easy cleanup if record is removed)
 * - Ensure thumbnails are generated
 * - Shorthands methods for standard sizes thumbnails (SmallAssetThumbnail, LargeAssetThumbnail)
 * - Smart cropping
 *
 * @property \SilverStripe\Assets\File|\SilverStripe\Assets\Image|\LeKoala\Base\Extensions\BaseFileExtension $owner
 * @property boolean $IsTemporary
 * @property int $ObjectID
 * @method \SilverStripe\ORM\DataObject Object()
 */
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

    /**
     * Resize and crop image to fill specified dimensions.
     * Use in templates with $SmartFill
     *
     * @link https://github.com/xymak/smartcrop.php
     * @param int $width Width to crop to
     * @param int $height Height to crop to
     * @return AssetContainer
     */
    public function SmartFill($width, $height)
    {
        $width = (int)$width;
        $height = (int)$height;
        $variant = $this->owner->variantName(__FUNCTION__, $width, $height);
        return $this->owner->manipulateImage($variant, function (Image_Backend $backend) use ($width, $height) {
            $clone = clone $backend;

            /* @var $resource Intervention\Image */
            $resource = clone $backend->getImageResource();

            // We default to center
            $x = ($resource->width() - $width) / 2;
            $y = ($resource->height() - $height) / 2;

            //TODO: use smartcrop analyze method and crop accordingly
            $resource->resize($width, $height);
            // $resource->crop($width, $height, $x, $y);
            $clone->setImageResource($resource);
            return $clone;
        });
    }

    public function SmallAssetThumbnail()
    {
        $w = UploadField::config()->uninherited('thumbnail_width');
        $h = UploadField::config()->uninherited('thumbnail_height');
        return $this->owner->ThumbnailIcon($w, $h);
    }

    public function LargeAssetThumbnail()
    {
        $w = AssetAdmin::config()->uninherited('thumbnail_width');
        $h = AssetAdmin::config()->uninherited('thumbnail_height');
        return $this->owner->ThumbnailIcon($w, $h);
    }

    /**
     * Cleanup object class fields
     *
     * @return void
     */
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
        // Set a default threshold if none set
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

    /**
     * Returns the full path of the file on the system
     *
     * Only works with local assets
     *
     * @return string
     */
    public function getFullPath()
    {
        // TODO: support custom path
        return Director::publicFolder() . '/assets/' . $this->getRelativePath();
    }

    /**
     * Get the path relative to the asset folder
     *
     * @return string
     */
    public function getRelativePath()
    {
        $Filename = $this->owner->FileFilename;
        $Dir = dirname($Filename);
        $Name = basename($Filename);

        $Hash = substr($this->owner->FileHash, 0, 10);

        $Path = '';
        // Is it protected?
        // TODO: support custom path
        if (!$this->owner->isPublished()) {
            $Path = '.protected/';
        }
        // TODO: legacy mode may be enabled
        $Path .= $Dir . '/' . $Hash . '/' . $Name;
        return $Path;
    }
}
