<?php
namespace LeKoala\Base\Dev\Tasks;

use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;

/**
 */
class PhpInfoTask extends BuildTask
{
    protected $description = 'Simply read your php info values.';
    private static $segment = 'PhpInfoTask';

    public function init()
    {
        echo phpinfo();
    }
}
