<?php
namespace LeKoala\Base\Forms\Tasks;

use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use LeKoala\Base\Extensions\BaseFileExtension;

/**
 */
class ClearTemporaryFilesTask extends BuildTask
{
    protected $description = 'Clear all temporary files from ajax uploads that didn\'t get attached to a record.';
    private static $segment = 'ClearTemporaryFilesTask';

    public function init()
    {
        $request = $this->getRequest();
        $this->addOption("go", "Tick this to remove the files", false);
        $options = $this->askOptions();

        $go = $options['go'];

        $files = BaseFileExtension::clearTemporaryUploads($go);

        if (empty($files)) {
            $this->message("No temporary file to delete");
            return;
        }
        if ($go) {
            $this->message("Deleting the following files");
        } else {
            $this->message("The following files will be deleted");
        }
        foreach ($files as $file) {
            $this->message($file->Filename);
        }
    }
}
