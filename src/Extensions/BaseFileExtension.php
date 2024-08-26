<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Convert;
use SilverStripe\Assets\Folder;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\View\Statically;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Helpers\FileHelper;
use LeKoala\Base\Security\Antivirus;
use SilverStripe\Core\Config\Config;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Assets\Image_Backend;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Assets\ImageBackendFactory;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Injector\InjectionCreator;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\Flysystem\ProtectedAssetAdapter;
use SilverStripe\Assets\Storage\Sha1FileHashingService;

/**
 * Improved File usage
 *
 * - Track temporary files (and auto clean them)
 * - Allow polymorphic association to a dedicated record (allows easy cleanup if record is removed)
 * - Ensure thumbnails are generated
 * - Shorthands methods for standard sizes thumbnails (SmallAssetThumbnail, LargeAssetThumbnail)
 * - Smart cropping
 *
 * @property \SilverStripe\Assets\File|\LeKoala\Base\Extensions\BaseFileExtension $owner
 * @property bool|int $IsTemporary
 * @property int $FileSize
 * @property int $ObjectID
 * @property string $ObjectClass
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

    /**
     * @config
     * @var bool
     */
    private static $enable_webp = false;

    private static $db = [
        // This helps tracking state of files uploaded through ajax uploaders
        "IsTemporary" => "Boolean",
        // Size in bytes
        "FileSize" => "Int",
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
        if (!$this->owner->FileSize && $this->owner->FileID) {
            $fs = $this->owner->getAbsoluteSize();
            if (!$fs) {
                $fs = filesize($this->owner->getFullPath());
            }
            $this->owner->FileSize = $fs;
        }
    }

    public function getHumanReadableSize()
    {
        return FileHelper::humanFilesize($this->owner->FileSize);
    }

    /**
     * Get total size for all assets
     *
     * @param bool $humanReadable
     * @return int|string
     */
    public static function getTotalAssetsSize($humanReadable = false)
    {
        $size = File::get()->sum('FileSize');
        if ($humanReadable) {
            return FileHelper::humanFilesize($size);
        }
        return $size;
    }

    /**
     * @param string|int $size
     * @return Image[]
     */
    public static function findLargeImages($size = null)
    {
        $mem = FileHelper::memoryLimit();
        if (!$size) {
            $size = '4mb';
        }
        if (!is_numeric($size)) {
            $size = FileHelper::convertToByte($size);
        }
        if ($size > $mem) {
            $size = $mem;
        }

        $files = Image::get()->where("FileSize > '$size'")->toArray();
        return $files;
    }

    public static function regenerateHashForId(int $id)
    {
        $service = new Sha1FileHashingService();

        $file = File::get_by_id($id);
        $hash = $file->getHash();

        $stream  = $file->getStream();
        if (!$stream) {
            $filename = $file->getFilename();
            $location = $file->getFullPath();
            if (!is_file($location)) {
                return false;
            }
            $stream = fopen($location, 'rb');
        }

        $fullhash = $service->computeFromStream($stream);

        if ($hash != $fullhash) {
            DB::query("UPDATE File SET FileHash = '" . $fullhash . "' WHERE ID = " . $file->ID);
            DB::query("UPDATE File_Live SET FileHash = '" . $fullhash . "' WHERE ID = " . $file->ID);
            return true;
        }
        return false;
    }

    public static function compressLargeFiles(int $width = 1600, int $height = 1600)
    {
        Environment::setTimeLimitMax(0);
        Environment::setMemoryLimitMax(0);

        $targetSize = FileHelper::convertToByte('4mb');
        $files = self::findLargeImages($targetSize);
        $log = [];
        foreach ($files as $file) {
            $size = $file->getAbsoluteSize();
            $path = $file->getFullPath();

            if (!$size) {
                $size = filesize($path);
            }

            list($w, $h) = getimagesize($path);
            if (!$w || !$h) {
                continue;
            }

            // It's already below target size
            $result = null;
            if ($size < $targetSize) {
                $result = 'skipped (file size is too small)';
                $file->FileSize = 0;
                $file->writeWithoutVersionIfPossible();
            } elseif ($w <= $width && $h <= $height) {
                $result = 'skipped (dimensions are too small)';
            }
            if ($result) {
                $log[] = [
                    'ID' => $file->ID,
                    'file' => $path,
                    'result' => $result,
                    'size' => $size,
                    'size_readable' => FileHelper::humanFilesize($size),
                    'w' => $w,
                    'h' => $h,
                    'new_size' => null,
                ];
                continue;
            }

            // keep in mind that hash will become invalid
            // https://github.com/silverstripe/silverstripe-assets/issues/378
            try {
                $result = FileHelper::imageResize($path, $path, $width, $height);
            } catch (\Throwable $e) {
                $result = $e->getMessage();
            }

            $newSize = $file->getAbsoluteSize();
            if (!$newSize) {
                $newSize = filesize($path);
            }
            if ($newSize != $size && $result == true) {
                $file->FileSize = 0;
                $file->writeWithoutVersionIfPossible();
            }

            $log[] = [
                'ID' => $file->ID,
                'file' => $path,
                'result' => $result,
                'size' => $size,
                'size_readable' => FileHelper::humanFilesize($size),
                'w' => $w,
                'h' => $h,
                'new_size' => $newSize,
            ];
        }

        return $log;
    }

    public static function moveFilesWithoutParent()
    {
        $files = File::get()->filter('ParentID', 0);
        $folder = Folder::find_or_make("Uploads");

        $setVersion = false;
        // file need to be unpublished first => otherwise no rename
        if (class_exists(Versioned::class) && Versioned::get_stage() !== Versioned::DRAFT) {
            $setVersion = true;
            Versioned::set_stage(Versioned::DRAFT);
        }

        foreach ($files as $file) {
            if ($file instanceof Folder) {
                continue;
            }

            $old =  $file->getFilename();
            $new = 'Uploads/' . $old;
            $file->setFilename($new);
            $file->write();
        }

        // they have a parent but no slash => maybe because they were renamed in live mode
        $files = File::get()->exclude('ParentID', 0)->where("FileFilename NOT LIKE '%/%'");
        foreach ($files as $file) {
            if ($file instanceof Folder) {
                continue;
            }

            $old =  $file->getFilename();
            $new = 'Uploads/' . $old;
            $file->setFilename($new);
            $file->write();
        }

        if ($setVersion) {
            Versioned::set_stage(Versioned::LIVE);
        }
    }

    /**
     * @return array An array of deleted files
     */
    public static function clearFileWithoutSize()
    {
        $files = File::get()->filter('FileSize', 0);
        $deleted = [];
        foreach ($files as $f) {
            if ($f instanceof Folder) {
                continue;
            }
            $size = $f->getAbsoluteSize();
            if ($size) {
                self::quickUpdateFileSize($f->ID, $size);
                continue;
            }

            // sometimes, getAbsoluteSize doesn't work
            $filesize = filesize($f->getFullPath());
            if ($filesize) {
                self::quickUpdateFileSize($f->ID, $filesize);
                continue;
            }

            $path = $f->getFullPath();
            if (!is_file($path)) {
                $deleted[] = $f->ID;
                $f->delete();
            }
        }

        return $deleted;
    }

    public function onAfterUpload()
    {
        // See BaseUpload::validate, it's better because it works on tmp file
        // if (Antivirus::isConfigured()) {
        //     Antivirus::scan($this->owner);
        // }
    }

    public function onAfterWrite()
    {
        $this->generateDefaultThumbnails();
    }

    /**
     * Get a list of files uploaded the given DataObject
     * It doesn't mean that the files are currently or still associated!!
     *
     * @param DataObject $record
     * @return \SilverStripe\ORM\DataList|File[]
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
     * This can also output a <picture> element with the webp alternative provided
     * that enable_webp is set to true and nomidi/silverstripe-webp-image is installed
     *
     * @param int $limitWidth
     * @return string
     */
    public function Lazy($limitWidth = null)
    {
        /** @var Image $img */
        $img = $this->owner;
        if ($limitWidth) {
            /** @var Image $img */
            $img = $this->owner->ScaleWidth($limitWidth);
        }
        if (!$img) {
            return;
        }
        $url = Convert::raw2att($img->getURL());
        $title = Convert::raw2att($img->getTitle());

        $ext = $img->getExtension();
        $webp_url = str_replace('.' . $ext, '_' . $ext, $url) . ".webp";

        $wh = '';
        if ($limitWidth) {
            // this reports original width if resized from template
            // so we have to pass width as an argument
            // @link https://github.com/silverstripe/silverstripe-assets/issues/201
            $w = $img->getWidth();
            $h = $img->getHeight();

            $wh = ' width="' . $w . '" height="' . $h . '"';
        }

        // @link https://github.com/verlok/vanilla-lazyload#lazy-responsive-image-with-automatic-webp-format-selection-using-the-picture-tag
        if (self::config()->enable_webp && in_array($ext, ["jpg", "png"]) && $img->isPublished()) {
            $this->createWebpIfNeeded($img);

            $html = '';
            $html .= '<source data-srcset="' . $webp_url . '" type="image/webp" />';
            $html .= '<img data-src="' . $url . '" class="lazy" alt="' . $title . '"' . $wh . ' />';
            return '<picture>' . $html . '</picture>';
        }
        return '<img data-src="' . $url . '" class="lazy" alt="' . $title . '"' . $wh . ' />';
    }

    protected function createWebpIfNeeded($img)
    {
        if (!$img->isPublished()) {
            return;
        }
        $ext = $img->getExtension();
        $path = $img->getFullPath();
        $store = $this->getAssetStore();
        $asUrl = Director::publicFolder() . $store->getAsURL($img->getFilename(), $img->getHash(), $img->getVariant());
        $webp_url = str_replace('.' . $ext, '_' . $ext, $asUrl) . ".webp";
        if (!is_file($webp_url)) {
            $store = $this->getAssetStore();
            // nomidi/silverstripe-webp-image or compatible
            $store->createWebPImage($path, $img->getFilename(), $img->getHash(), $img->getVariant()); // @intelephense-ignore-line
        }
    }

    public function WebpLink()
    {
        /** @var Image $img */
        $img = $this->owner;
        $ext = $img->getExtension();
        if (self::config()->enable_webp && in_array($ext, ["jpg", "png"]) && $img->isPublished()) {
            $this->createWebpIfNeeded($img);
            $webp_url = str_replace('.' . $ext, '_' . $ext, $img->Link()) . ".webp";
            return $webp_url;
        }
        return $img->Link();
    }

    public function Base64($width = 0)
    {
        /** @var Image $resized */
        $resized = $width > 0 ? $this->owner->ScaleWidth($width) : $this->owner;
        if ($resized && $resized->exists()) {
            $str = $resized->getString();
            $mime = $resized->getMimeType();
            $img_src = "data:$mime;base64," . str_replace("\n", "", base64_encode($str));
            return $img_src;
        }
    }

    /**
     * @return AssetStore|Nomidi\WebPCreator\Flysystem\FlysystemAssetStore
     */
    protected function getAssetStore()
    {
        return Injector::inst()->get(AssetStore::class);
    }

    /**
     * Get a cdn version of the image
     *
     * @param int $width
     * @param int $height
     * @return string
     */
    public function StaticallyLink($width = null, $height = null)
    {
        $params = [
            'f=auto'
        ];
        if ($width) {
            $params[] = "w=$width";
        }
        if ($height) {
            $params[] = "h=$height";
        }
        return Statically::img($this->owner->AbsoluteLink(), $params);
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

    public static function quickUpdateFileSize(int $id, int $fs)
    {
        DB::query("UPDATE File SET FileSize = $fs WHERE ID = $id");
        DB::query("UPDATE File_Live SET FileSize = $fs WHERE ID = $id");
        DB::query("UPDATE File_Versions SET FileSize = $fs WHERE RecordID = $id");

        self::regenerateHashForId($id);
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
        return ASSETS_PATH . '/' . $this->getRelativePath();
    }

    public function getProtectedFullPath()
    {
        return self::getBaseProtectedPath() . '/' . $this->getRelativePath();
    }

    public static function getBaseProtectedPath()
    {
        // Use environment defined path or default location is under assets
        if ($path = Environment::getEnv('SS_PROTECTED_ASSETS_PATH')) {
            return $path;
        }

        // Default location
        return ASSETS_PATH . '/' . Config::inst()->get(ProtectedAssetAdapter::class, 'secure_folder');
    }

    /**
     * Get the path relative to the asset folder
     *
     * @return string
     */
    public function getRelativePath()
    {
        if ($this->owner instanceof Folder) {
            $obj = $this->owner;
            $parts = [];
            while ($obj->ParentID) {
                $parts[] = $obj->Name;
                $obj = $obj->Parent();
            }
            $parts[] = $obj->Name;
            $parts = array_reverse($parts);
            return implode("/", $parts);
        }
        $Filename = $this->owner->FileFilename;
        if (!$Filename) {
            return "";
        }

        $Dir = dirname($Filename);
        $Name = basename($Filename);

        $Hash = substr($this->owner->FileHash, 0, 10);

        $Path = '';
        // Is it protected?
        if (!$this->owner->isPublished() && $Hash) {
            $Path = Config::inst()->get(ProtectedAssetAdapter::class, 'secure_folder') . '/';
            $Path .= $Dir . '/' . $Hash . '/' . $Name;
        } else {
            // Check legacy_filenames=true
            // With SilverStripe 4.4.0, public files are "hash-less" by default
            $Path .= $Dir . '/' . $Name;
        }
        return $Path;
    }
}
