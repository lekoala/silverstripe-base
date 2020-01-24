<?php

namespace LeKoala\Base\Forms;

use InvalidArgumentException;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Upload;
use LeKoala\Base\Helpers\ClassHelper;

trait BaseFileUploadReceiver
{
    /**
     * Set default description
     *
     * @param string $relation Type of relation, eg Image or File
     * @param DataObject $record A related record
     * @param string $name Relation name, eg "Logo"
     * @return string
     */
    protected function setDefaultDescription($relation, $record = null, $name = null)
    {
        $desc = '';
        $size = File::format_size($this->getValidator()->getAllowedMaxFileSize());
        $desc = _t('BaseFileUploadReceiver.MAXSIZE', 'Max file size: {size}', ['size' => $size]);
        if ($relation == Image::class) {
            $desc .= '; ';

            // do we have a preferred size?
            $preferredSize = false;
            if ($record) {
                $sizes = $record->config()->image_sizes;
                if ($sizes && isset($sizes[$name])) {
                    $size = $sizes[$name][0] . 'x' . $sizes[$name][1];
                    $desc .= _t('BaseFileUploadReceiver.RECOMMENDEDSIZE', 'Recommended resolution: ' . $size . 'px');
                    $preferredSize = true;
                }
            }
            if (!$preferredSize) {
                $desc .= _t('BaseFileUploadReceiver.MAXRESOLUTION', 'Max resolution: 2048x2048px');
            }
        }
        $extensions = $this->getAllowedExtensions();
        if (count($extensions) < 7) {
            $desc .= '; ';
            $desc .= _t('BaseFileUploadReceiver.ALLOWEXTENSION', 'Allowed extensions: {ext}', array('ext' => implode(',', $extensions)));
        }
        $this->description = $desc;
    }

    protected function getDefaultFolderName()
    {
        // There is no record, use default upload folder
        if (!$this->record) {
            return Upload::config()->uploads_folder;
        }
        // The record can determine its upload folder
        if ($this->record->hasMethod('getFolderName')) {
            return $this->record->getFolderName();
        }
        // Have a sane default for others
        $class = ClassHelper::getClassWithoutNamespace($this->record);
        $name = str_replace('[]', '', $this->getName());
        return $class . '/' . $name;
    }

    /**
     * Get the rename pattern if set
     * (proxy of BaseUpload method for ease of use)
     *
     * @return string
     */
    public function getRenamePattern()
    {
        return $this->getUpload()->getRenamePattern();
    }

    /**
     * Rename pattern can use the following variables:
     * - {field}
     * - {name}
     * - {basename}
     * - {extension}
     * - {timestamp}
     * - {date}
     * - {datetime}
     * (proxy of BaseUpload method for ease of use)
     *
     * @param string $renamePattern
     * @return $this
     */
    public function setRenamePattern($renamePattern)
    {
        $renamePattern = str_replace('{field}', $this->getName(), $renamePattern);
        // Basic check for extension
        if (strpos($renamePattern, '.') === false && strpos($renamePattern, '{name}') === false) {
            throw new InvalidArgumentException("Pattern $renamePattern should contain an extension");
        }
        $this->getUpload()->renamePattern = $renamePattern;
        return $this;
    }
}
