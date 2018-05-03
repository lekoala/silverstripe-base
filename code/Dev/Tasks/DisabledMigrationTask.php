<?php

namespace LeKoala\Base\Dev\Tasks;

use SilverStripe\Dev\MigrationTask;

/**
 * This is just to hide a useless migration task
 */
class DisabledMigrationTasks extends MigrationTask
{

    public function isEnabled()
    {
        return get_called_class() != self::class;
    }
}
