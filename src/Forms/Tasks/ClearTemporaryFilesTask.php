<?php

namespace LeKoala\Base\Forms\Tasks;

use SilverStripe\Dev\BuildTask;
use LeKoala\Base\Extensions\BaseFileExtension;
use LeKoala\DevToolkit\BuildTaskTools;

/**
 */
class ClearTemporaryFilesTask extends BuildTask
{
    use BuildTaskTools;

    protected $description = 'Clear all temporary files from ajax uploads that didn\'t get attached to a record.';
    private static $segment = 'ClearTemporaryFilesTask';

    public function run($request)
    {
        $this->request = $request;
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
