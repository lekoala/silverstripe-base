<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Assets\File;
use SilverStripe\Control\HTTP;
use SilverStripe\Assets\Folder;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataObjectInterface;

/**
 * A file pond field
 *
 * TODO: Support all plugins
 */
class FilePondField extends BaseFileUploadField
{

    /**
     * @config
     * @var array
     */
    private static $allowed_actions = [
        'upload'
    ];

    /**
     * @config
     * @var int
     */
    private static $thumbnail_width = 60;

    /**
     * @config
     * @var int
     */
    private static $thumbnail_height = 60;

    /**
     * @config
     * @var string
     */
    private static $temporary_folder = 'TemporaryUploads';

    /**
     * Set if uploading new files is enabled.
     * If false, only existing files can be selected
     *
     * @var bool
     */
    protected $uploadEnabled = true;

    /**
     * Set if selecting existing files is enabled.
     * If false, only new files can be selected.
     *
     * @var bool
     */
    protected $attachEnabled = true;

    /**
     * The number of files allowed for this field
     *
     * @var null|int
     */
    protected $allowedMaxFileNumber = null;

    protected $inputType = 'file';

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;

    protected $schemaComponent = 'FilePond';

    /**
     * @var bool|null
     */
    protected $multiUpload = null;

    public function setValue($value, $record = null)
    {
        if (is_numeric($value)) {
            $value = $this->getTemporaryFile($value);
        }
        return parent::setValue($value, $record);
    }

    /**
     * @param int $ID
     * @return File
     */
    protected function getTemporaryFile($ID)
    {
        $file = File::get()->byID($ID);
        return $file;
    }

    /**
     * Array of accepted file types.
     * Can be mime types or wild cards. For instance ['image/*']
     * will accept all images. ['image/png', 'image/jpeg']
     * will only accepts PNGs and JPEGs.
     *
     * @return array
     */
    public function getAcceptedFileTypes()
    {
        $validator = $this->getValidator();
        $extensions = $validator->getAllowedExtensions();
        $mimeTypes = HTTP::config()->uninherited('MimeTypes');

        $arr = [];
        foreach ($extensions as $ext) {
            if (isset($mimeTypes[$ext])) {
                $arr[] = $mimeTypes[$ext];
            }
        }
        return $arr;
    }

    /**
     * The maximum size of a file, for instance 5MB or 750KB
     *
     * @return string
     */
    public function getMaxFileSize()
    {
        return str_replace(' ', '', File::format_size($this->getValidator()->getAllowedMaxFileSize()));
    }

    /**
     * Configure our endpoint
     *
     * @link https://pqina.nl/filepond/docs/patterns/api/server/
     * @return void
     */
    public function getServerOptions()
    {
        return [
            'process' =>  $this->getUploadEnabled() ? $this->getLinkParameters('upload') : null,
            'fetch' => null,
            'revert' => null,
        ];
    }

    /**
     * Configure the following parameters:
     *
     * url : Path to the end point
     * method : Request method to use
     * withCredentials : Toggles the XMLHttpRequest withCredentials on or off
     * headers : An object containing additional headers to send
     * timeout : Timeout for this action
     * onload : Called when server response is received, useful for getting the unique file id from the server response
     * onerror : Called when server error is received, receis the response body, useful to select the relevant error data
     *
     * @param string $action
     * @return array
     */
    protected function getLinkParameters($action)
    {
        $token = $this->getForm()->getSecurityToken()->getValue();
        return [
            'url' => $this->Link($action),
            'headers' => [
                'X-SecurityID' => $token
            ],
        ];
    }

    public function Field($properties = array())
    {
        $name = $this->getName();
        $multiple =$this->getIsMultiUpload();

        //TODO: multiple is not working correctly atm
        //https://github.com/pqina/filepond/issues/50
        $config = [
            'name' => $name,
            'allowMultiple' => $multiple,
            'allowFileTypeValidation' => true,
            'acceptedFileTypes' => $this->getAcceptedFileTypes(),
            'maxFiles' => $this->getAllowedMaxFileNumber(),
            'maxFileSize' => $this->getMaxFileSize(),
            'server' => $this->getServerOptions(),
        ];
        $this->setAttribute('data-module', 'filepond');
        $this->setAttribute('data-config', json_encode($config));

        Requirements::css('https://unpkg.com/filepond/dist/filepond.css');
        Requirements::javascript('https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js');
        Requirements::javascript('https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js');
        Requirements::javascript('https://unpkg.com/filepond/dist/filepond.min.js');
        Requirements::javascript('https://unpkg.com/jquery-filepond/filepond.jquery.js');
        Requirements::javascript('base/javascript/fields/FilePondField.js');
        Requirements::javascript('base/javascript/ModularBehaviour.js');

        return parent::Field($properties);
    }

    /**
     * Creates a single file based on a form-urlencoded upload.
     *
     * 1 client uploads file my-file.jpg as multipart/form-data using a POST request
     * 2 server saves file to unique location tmp/12345/my-file.jpg
     * 3 server returns unique location id 12345 in text/plain response
     * 4 client stores unique id 12345 in a hidden input field
     * 5 client submits the FilePond parent form containing the hidden input field with the unique id
     * 6 server uses the unique id to move tmp/12345/my-file.jpg to its final location and remove the tmp/12345 folder
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function upload(HTTPRequest $request)
    {
        if ($this->isDisabled() || $this->isReadonly()) {
            return $this->httpError(403);
        }

        // CSRF check
        $token = $this->getForm()->getSecurityToken();
        if (!$token->checkRequest($request)) {
            return $this->httpError(400, "Invalid token");
        }

        $name = $this->getName();
        $tmpFile = $request->postVar($name);
        if (!$tmpFile) {
            return $this->httpError(400, "No file");
        }

        // Override folder name to ensure the file goes to a temp folder
        $folderName = $this->getFolderName();
        $this->setFolderName($this->getTemporaryFolderName());
        /** @var File $file */
        $file = $this->saveTemporaryFile($tmpFile, $error);
        $this->setFolderName($folderName);

        // Prepare result
        if ($error) {
            $this->getUpload()->clearErrors();
            return $this->httpError(400, json_encode($error));
        }

        $this->getUpload()->clearErrors();
        $fileId = $file->ID;

        $response = new HTTPResponse($fileId);
        $response->addHeader('Content-Type', 'text/plain');

        return $response;
    }

    /**
     * @return string
     */
    protected function getTemporaryFolderName()
    {
        return self::config()->temporary_folder;
    }

    public function Type()
    {
        return 'filepond';
    }
}
