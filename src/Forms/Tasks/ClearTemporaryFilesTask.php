<?php

namespace LeKoala\Base\Forms\Tasks;

use SilverStripe\Dev\BuildTask;
use LeKoala\Base\Extensions\BaseFileExtension;
use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 */
class ClearTemporaryFilesTask extends BuildTask
{
    use BuildTaskTools;

    protected static string $commandName = 'clear-temporary-files';
    protected string $title = 'Clear temporary upload files';
    protected static string $description =
        'Clear temporary files from AJAX uploads that were not attached to a record.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->setTaskContext($input, $output);
        $go = (bool) $this->taskOption('go');

        $files = BaseFileExtension::clearTemporaryUploads($go);

        if (empty($files)) {
            $this->message("No temporary file to delete");
            return Command::SUCCESS;
        }
        if ($go) {
            $this->message("Deleting the following files");
        } else {
            $this->message("The following files will be deleted");
        }
        foreach ($files as $file) {
            $this->message($file->Filename);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<InputOption>
     */
    public function getOptions(): array
    {
        return [
            new InputOption('go', null, InputOption::VALUE_NONE, 'Delete listed temporary files'),
        ];
    }
}
