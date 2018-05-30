<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Assets\Upload;
use SilverStripe\ORM\DataObject;

class BaseUpload extends Upload
{
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
        $filename = $this->resolveExistingFile($filename);

        // Save changes to underlying record (if it's a DataObject)
        $this->storeTempFile($tmpFile, $filename, $this->file);
        if ($this->file instanceof DataObject) {
            $this->file->IsTemporary = true;
            $this->file->write();
        }

        //to allow extensions to e.g. create a version after an upload
        $this->file->extend('onAfterUpload');
        $this->extend('onAfterLoadIntoFile', $this->file);
        return true;
    }
}
