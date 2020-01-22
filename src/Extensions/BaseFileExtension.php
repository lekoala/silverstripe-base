<?php

namespace LeKoala\Base\Extensions;

use Exception;
use LeKoala\Base\View\Statically;
use SilverStripe\ORM\DB;
use SilverStripe\Assets\File;
use SilverStripe\Core\Convert;
use SilverStripe\Assets\Folder;
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
 * @property \SilverStripe\Assets\File|\SilverStripe\Assets\Folder|\SilverStripe\Assets\Image|\LeKoala\Base\Extensions\BaseFileExtension $owner
 * @property boolean $IsTemporary
 * @property int $ObjectID
 * @method \SilverStripe\ORM\DataObject Object()
 */
class BaseFileExtension extends DataExtension
{
    use Configurable;

    private static $casting = [
        "Lazy" => 'HTMLFragment',
    ];

    /**
     * @config
     * @var string
     */
    private static $auto_clear_threshold = null;

    private static $db = [
        // This helps tracking state of files uploaded through ajax uploaders
        "IsTemporary" => "Boolean",
    ];
    private static $has_one = [
        // Record is already used by versioned extensions
        // ChangeSetItem already uses Object convention so use the same
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

    /**
     * Get a list of files for the given DataObject
     *
     * @param DataObject $record
     * @return DataList|File[]
     */
    public static function getObjectFiles(DataObject $record)
    {
        return File::get()->filter([
            'ObjectID' => $record->ID,
            'ObjectClass' => get_class($record),
        ])->exclude('IsTemporary', 1);
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
     * Simply use MyImage.Lazy in your templates
     *
     * Pass MyImage.Lazy(200) if you don't want to limit width to a specific size
     *
     * Currently, boolean arguments are not supported in a template
     * @link https://github.com/silverstripe/silverstripe-framework/issues/8690
     *
     * @param int $limitWidth
     * @return string
     */
    public function Lazy($limitWidth = null)
    {
        $img = $this->owner;
        if ($limitWidth) {
            $img = $this->owner->ScaleWidth($limitWidth);
        }
        $url = Convert::raw2att($img->getURL());
        $title = Convert::raw2att($img->getTitle());
        if (!$limitWidth) {
            return '<img data-src="' . $url . '" class="lazy" alt="' . $title . '" />';
        }
        // this reports original width if resized from template
        // so we have to pass width as an argument
        // @link https://github.com/silverstripe/silverstripe-assets/issues/201
        $w = $img->getWidth();
        $h = $img->getHeight();
        return '<img data-src="' . $url . '" class="lazy" alt="' . $title . '" width="' . $w . '" height="' . $h . '" />';
    }

    /**
     * Get a cdn version of the image
     *
     * @return string
     */
    public function StaticallyLink()
    {
        return Statically::img($this->owner->AbsoluteLink());
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
        $width = (int) $width;
        $height = (int) $height;
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
        if ($this->owner instanceof Folder) {
            throw new Exception("This method is not supported for folders");
        }
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
