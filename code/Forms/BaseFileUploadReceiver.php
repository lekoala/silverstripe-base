<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Upload;
use LeKoala\Base\Helpers\ClassHelper;

trait BaseFileUploadReceiver
{
    protected function setDefaultDescription($relation)
    {
        $desc = '';
        $size = File::format_size($this->getValidator()->getAllowedMaxFileSize());
        switch ($relation) {
            case Image::class:
                $desc = _t('SmartUploadField.MAXSIZE', 'Max file size: {size}', ['size' => $size]);
                $desc .= '; ';
                $desc .= _t('SmartUploadField.MAXRESOLUTION', 'Max resolution: 2048x2048px; Allowed extensions: {ext}', array('ext' => implode(',', $this->getAllowedExtensions())));
                break;
            default:
                $desc = _t('SmartUploadField.MAXSIZE', 'Max file size: {size}', ['size' => $size]);
                break;
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
        $class = ClassHelper::getClassWithoutNamespace($record);
        $name = $this->getName();
        return $class . '/' . $name;
    }
}
