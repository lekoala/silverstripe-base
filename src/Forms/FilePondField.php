<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Assets\File;
use SilverStripe\Control\HTTP;
use SilverStripe\Assets\Folder;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FormField;
use LeKoala\Base\Forms\BaseUpload;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataObjectInterface;
use LeKoala\Base\Extensions\BaseFileExtension;
use SilverStripe\Assets\Image;
use SilverStripe\Security\Member;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ArrayList;

/**
 * A FilePond field
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
     * @var boolean
     */
    private static $auto_clear_temp_folder = true;

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

    // Schema needs to be something else than custom otherwise it fails on ajax load because
    // we don't have a proper react component
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_HIDDEN;
    protected $schemaComponent = null;

    /**
     * @var bool|null
     */
    protected $multiUpload = null;

    /**
     * Retrieves the Upload handler
     *
     * The Upload class is overrided in the yml class
     *
     * @return BaseUpload
     */
    public function getUpload()
    {
        return $this->upload;
    }

    public function setValue($value, $record = null)
    {
        // Normalize values to something similar to UploadField usage
        if (is_numeric($value)) {
            $value = ['Files' => [$value]];
        } elseif (is_array($value) && empty($value['Files'])) {
            $value = ['Files' => $value];
        }
        // Track existing record data
        if ($record) {
            $name = $this->name;
            if ($record instanceof DataObject && $record->hasMethod($name)) {
                $data = $record->$name();
                // Wrap
                if ($data instanceof DataObject) {
                    $data = new ArrayList([$data]);
                }
                foreach ($data as $uploadedItem) {
                    $this->trackFileID($uploadedItem->ID);
                }
            }
        }
        return parent::setValue($value, $record);
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
     * @return array
     */
    public function getServerOptions()
    {
        return [
            'process' => $this->getUploadEnabled() ? $this->getLinkParameters('upload') : null,
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
        $record = $this->getForm()->getRecord();

        $headers = [
            'X-SecurityID' => $token
        ];
        // Allow us to track the record instance
        if ($record) {
            $headers['X-RecordClassName'] = get_class($record);
            $headers['X-RecordID'] = $record->ID;
        }
        return [
            'url' => $this->SafeLink($action),
            'headers' => $headers,
        ];
    }

    /**
     * @return string
     */
    public function getSafeName()
    {
        return str_replace('[]', '', $this->getName());
    }

    /**
     * Return a link to this field.
     *
     * @param string $action
     * @return string
     */
    public function SafeLink($action = null)
    {
        return Controller::join_links($this->form->FormAction(), 'field/' . $this->getSafeName(), $action);
    }

    /**
     * Set initial values to FilePondField
     * See: https://pqina.nl/filepond/docs/patterns/api/filepond-object/#setting-initial-files
     *
     * @return array
     */
    public function getExistingUploadsData()
    {
        // Both Value() & dataValue() seem to return an array eg: ['Files' => [258, 259, 257]]
        $fileIDarray = $this->Value() ?: ['Files' => []];
        if (!isset($fileIDarray['Files']) || !count($fileIDarray['Files'])) {
            return [];
        }

        $existingUploads = [];
        foreach ($fileIDarray['Files'] as $fileID) {
            /* @var $file File */
            $file = File::get()->byID($fileID);
            if (!$file) {
                continue;
            }
            // $poster = null;
            // if ($file instanceof Image) {
            //     $w = self::config()->get('thumbnail_width');
            //     $h = self::config()->get('thumbnail_height');
            //     $poster = $file->Fill($w, $h)->getAbsoluteURL();
            // }
            $existingUploads[] = [
                // the server file reference
                'source' => (int) $fileID,
                // set type to local to indicate an already uploaded file
                'options' => [
                    'type' => 'local',
                    // file information
                    'file' => [
                        'name' => $file->Name,
                        'size' => (int) $file->getAbsoluteSize(),
                        'type' => $file->getMimeType(),
                    ],
                    // poster
                    // 'metadata' => [
                    //     'poster' => $poster
                    // ]
                ],

            ];
        }
        return $existingUploads;
    }

    public function FieldHolder($properties = array())
    {
        $name = $this->getName();
        $multiple = $this->getIsMultiUpload();
        if ($multiple && strpos($name, '[]') === false) {
            $name .= '[]';
            $this->setName($name);
        }

        $i18nConfig = [
            'labelIdle' => _t('FilePondField.labelIdle', 'Drag & Drop your files or <span class="filepond--label-action"> Browse </span>'),
            'labelFileProcessing' => _t('FilePondField.labelFileProcessing', 'Uploading'),
            'labelFileProcessingComplete' => _t('FilePondField.labelFileProcessingComplete', 'Upload complete'),
            'labelFileProcessingAborted' => _t('FilePondField.labelFileProcessingAborted', 'Upload cancelled'),
            'labelTapToCancel' => _t('FilePondField.labelTapToCancel', 'tap to cancel'),
            'labelTapToRetry' => _t('FilePondField.labelTapToCancel', 'tap to retry'),
            'labelTapToUndo' => _t('FilePondField.labelTapToCancel', 'tap to undo'),
        ];
        $config = [
            'name' => $name, // This will also apply to the hidden fields
            'allowMultiple' => $multiple,
            'acceptedFileTypes' => $this->getAcceptedFileTypes(),
            'maxFiles' => $this->getAllowedMaxFileNumber(),
            'maxFileSize' => $this->getMaxFileSize(),
            'server' => $this->getServerOptions(),
            'files' => $this->getExistingUploadsData(),
        ];
        $config = array_merge($config, $i18nConfig);

        $this->setAttribute('data-module', 'filepond');
        $this->setAttribute('data-config', json_encode($config));

        // Polyfill to ensure max compatibility
        Requirements::javascript("https://unpkg.com/filepond-polyfill@1.0.4/dist/filepond-polyfill.min.js");
        // File validation plugins
        Requirements::javascript("https://unpkg.com/filepond-plugin-file-validate-type@1.2.5/dist/filepond-plugin-file-validate-type.min.js");
        Requirements::javascript("https://unpkg.com/filepond-plugin-file-validate-size@2.2.1/dist/filepond-plugin-file-validate-size.min.js");
        // Poster plugins
        // Requirements::javascript("https://unpkg.com/filepond-plugin-file-metadata@1.0.2/dist/filepond-plugin-file-metadata.min.js");
        // Requirements::css("https://unpkg.com/filepond-plugin-file-poster@1.0.0/dist/filepond-plugin-file-poster.min.css");
        // Requirements::javascript("https://unpkg.com/filepond-plugin-file-poster@1.0.0/dist/filepond-plugin-file-poster.min.js");
        // Image plugins
        Requirements::javascript("https://unpkg.com/filepond-plugin-image-exif-orientation@1.0.9/dist/filepond-plugin-image-exif-orientation.js");
        // Requirements::css("https://unpkg.com/filepond-plugin-image-preview@2.0.1/dist/filepond-plugin-image-preview.min.css");
        // Requirements::javascript("https://unpkg.com/filepond-plugin-image-preview@2.0.1/dist/filepond-plugin-image-preview.min.js");
        // Base elements
        Requirements::css("https://unpkg.com/filepond@4.20.1/dist/filepond.css");
        Requirements::javascript("https://unpkg.com/filepond@4.20.1/dist/filepond.js");
        Requirements::javascript("https://unpkg.com/jquery-filepond@1.0.0/filepond.jquery.js");
        // Our custom init
        Requirements::javascript('base/javascript/ModularBehaviour.js');
        Requirements::javascript('base/javascript/fields/FilePondField.js');

        return parent::FieldHolder($properties);
    }

    public function Field($properties = array())
    {
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
        $tmpFile = $this->normalizeTempFile($tmpFile);

        /** @var File $file */
        $file = $this->saveTemporaryFile($tmpFile, $error);

        // Prepare result
        if ($error) {
            $this->getUpload()->clearErrors();
            return $this->httpError(400, json_encode($error));
        }

        if ($file instanceof DataObject && $file->hasExtension(BaseFileExtension::class)) {
            $file->IsTemporary = true;
            // We can also track the record
            $RecordID = $request->getHeader('X-RecordID');
            $RecordClassName = $request->getHeader('X-RecordClassName');
            if (!$file->ObjectID) {
                $file->ObjectID = $RecordID;
            }
            if (!$file->ObjectClass) {
                $file->ObjectClass = $RecordClassName;
            }
            // If possible, prevent creating a version for no reason
            // @link https://docs.silverstripe.org/en/4/developer_guides/model/versioning/#writing-changes-to-a-versioned-dataobject
            if ($file->hasExtension(Versioned::class)) {
                $file->writeWithoutVersion();
            } else {
                $file->write();
            }
        }

        // Because the file is not linked to anything, it's public by default
        // This also kills the tracking of the physical file so don't do it
        // if ($file->getVisibility() == 'public') {
        //     $file->protectFile();
        // }

        $this->getUpload()->clearErrors();
        $fileId = $file->ID;
        $this->trackFileID($fileId);

        // Prepare response
        $response = new HTTPResponse($fileId);
        $response->addHeader('Content-Type', 'text/plain');

        if (self::config()->auto_clear_temp_folder) {
            BaseFileExtension::clearTemporaryUploads(true);
        }

        return $response;
    }

    /**
     * Allows tracking uploaded ids to prevent unauthorized attachements
     *
     * @param int $fileId
     * @return void
     */
    public function trackFileID($fileId)
    {
        $session = $this->getRequest()->getSession();
        $uploadedIDs = $this->getTrackedIDs();
        if (!in_array($fileId, $uploadedIDs)) {
            $uploadedIDs[] = $fileId;
        }
        $session->set('FilePond', $uploadedIDs);
    }

    /**
     * Get all authorized tracked ids
     * @return array
     */
    public function getTrackedIDs()
    {
        $session = $this->getRequest()->getSession();
        $uploadedIDs = $session->get('FilePond');
        if ($uploadedIDs) {
            return $uploadedIDs;
        }
        return [];
    }

    public function saveInto(DataObjectInterface $record)
    {
        // Note that the list of IDs is based on the value sent by the user
        // It can be spoofed because checks are minimal (by default, canView = true and only check if isInDB)
        $IDs = $this->getItemIDs();

        $MemberID = Member::currentUserID();

        // Ensure the files saved into the DataObject have been tracked (either because already on the DataObject or uploaded by the user)
        $trackedIDs = $this->getTrackedIDs();
        foreach ($IDs as $ID) {
            if (!in_array($ID, $trackedIDs)) {
                throw new ValidationException("Invalid file ID : $ID");
            }
        }

        // Move files out of temporary folder
        foreach ($IDs as $ID) {
            $file = $this->getFileByID($ID);
            if ($file && $file->IsTemporary) {
                // The record does not have an ID which is a bad idea to attach the file to it
                if (!$record->ID) {
                    $record->write();
                }
                // Check if the member is owner
                if ($MemberID && $MemberID != $file->OwnerID) {
                    throw new ValidationException("Failed to authenticate owner");
                }
                $file->IsTemporary = false;
                $file->ObjectID = $record->ID;
                $file->ObjectClass = get_class($record);
                $file->write();

                // Do we need to relocate the asset?
                // if ($record->hasMethod('getFolderName')) {
                //     $recordFolder = $record->getFolderName();
                //     $fileFolder = dirname($file->getFilename());
                //     if ($recordFolder != $fileFolder) {
                //         $newName = $recordFolder . '/' . basename($file->getFilename());
                //         $file->renameFile($newName);
                //     }
                // }
            } else {
                // File was uploaded earlier, no need to do anything
            }
        }

        // Proceed
        return parent::saveInto($record);
    }

    /**
     * @param int $ID
     * @return File
     */
    protected function getFileByID($ID)
    {
        return File::get()->byID($ID);
    }

    /**
     * Convert an array of file to a single file
     *
     * @param array $tmpFile
     * @return array
     */
    protected function normalizeTempFile($tmpFile)
    {
        $newTmpFile = [];
        foreach ($tmpFile as $k => $v) {
            if (is_array($v)) {
                $v = $v[0];
            }
            $newTmpFile[$k] = $v;
        }
        return $newTmpFile;
    }

    public function Type()
    {
        return 'filepond';
    }
}
