<?php
namespace LeKoala\Base\Dev\Tasks;

use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;

/**
 */
class PhpInfoTask extends BuildTask
{

    protected $title = "Php Info";
    protected $description = 'Simply read your php info values.';
    private static $segment = 'PhpInfoTask';

    public function init(HTTPRequest $request)
    {
        echo phpinfo();
    }
}
