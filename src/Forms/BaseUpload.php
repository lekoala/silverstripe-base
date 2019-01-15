<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Assets\Upload;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\FileNameFilter;

/**
 * This extension of the Upload class allow setting a rename pattern for the file
 *
 * This is done through the Upload class to allow setting the pattern from the controller
 *
 * ? maybe this should be more convention based and use the BaseFileExtension instead
 *
 */
class BaseUpload extends Upload
{
    /**
     * @var string
     */
    protected $renamePattern;

    /**
     * Save an file passed from a form post into this object.
     * File names are filtered through {@link FileNameFilter}, see class documentation
     * on how to influence this behaviour.
     *
     * @param array $tmpFile
     * @param AssetContainer $file
     * @param string|bool $folderPath
     * @return bool True if the file was successfully saved into this record
     * @throws Exception
     */
    public function loadIntoFile($tmpFile, $file = null, $folderPath = false)
    {
        $this->file = $file;

        // Validate filename
        $filename = $this->getValidFilename($tmpFile, $folderPath);
        if (!$filename) {
            return false;
        }

        // Rename pattern
        if ($this->renamePattern) {
            $filename = $this->changeFilenameWithPattern($filename, $this->renamePattern);
        }

        $oldFilename = $filename;
        $filename = $this->resolveExistingFile($filename);

        // Save changes to underlying record (if it's a DataObject)
        $this->storeTempFile($tmpFile, $filename, $this->file);

        //to allow extensions to e.g. create a version after an upload
        $this->file->extend('onAfterUpload');
        $this->extend('onAfterLoadIntoFile', $this->file);
        return true;
    }

    /**
     * Rename pattern can use the following variables:
     * - {name}
     * - {basename}
     * - {extension}
     * - {timestamp}
     * - {date}
     * - {datetime}
     *
     * @param string $filename The filename, including the folder path
     * @param string $pattern
     * @return string The filename
     */
    protected function changeFilenameWithPattern($filename, $pattern)
    {
        $folder = dirname($filename);
        $name = pathinfo($filename, PATHINFO_BASENAME);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $map = [
            '{name}' => $name,
            '{basename}' => $basename,
            '{extension}' => $extension,
            '{timestamp}' => time(),
            '{date}' => date('Ymd'),
            '{datetime}' => date('Ymd_His'),
        ];
        $search = array_keys($map);
        $replace = array_values($map);
        $filename = str_replace($search, $replace, $pattern);

        // Ensure end result is valid
        $filter = new FileNameFilter;
        $filename = $filter->filter($filename);

        if ($folder) {
            $filename = $folder . '/' . $filename;
        }

        return $filename;
    }

    /**
     * Get the value of renamePattern
     *
     * @return string
     */
    public function getRenamePattern()
    {
        return $this->renamePattern;
    }

    /**
     * Set the value of renamePattern
     *
     * @param string $renamePattern
     * @return $this
     */
    public function setRenamePattern($renamePattern)
    {
        $this->renamePattern = $renamePattern;
        return $this;
    }
}
