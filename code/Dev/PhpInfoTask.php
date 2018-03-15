<?php
namespace LeKoala\Base\Dev;

use LeKoala\Base\Dev\BuildTask;


/**
 */
class PhpInfoTask extends BuildTask
{

    protected $title = "Php Info";
    protected $description = 'Simply read your php info values.';
    private static $segment = 'PhpInfoTask';

    public function init()
    {
        echo phpinfo();
    }
}
