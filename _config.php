<?php

use SilverStripe\Control\Director;

// Add a benchmark helper
if (!function_exists('bm')) {
    function bm($cb = null)
    {
        \LeKoala\Base\Dev\Benchmark::run($cb);
    }
}
// Add a debug helper
if (!function_exists('d')) {
    function d()
    {
        // Don't show on live
        if (Director::isLive()) {
            return;
        }

        $debugView = \SilverStripe\Dev\Debug::create_debug_view();
        $i = 0;
        foreach (func_get_args() as $val) {
            echo $debugView->debugVariable($val, \SilverStripe\Dev\Debug::caller(), true, $i);
            $i++;
        }
        exit();
    }
}
// Add a logger helper
if (!function_exists('l')) {
    function l()
    {
        $priority = 100;
        $extras = func_get_args();
        $message = array_shift($extras);
        \SilverStripe\Core\Injector\Injector::inst()->get(\Psr\Log\LoggerInterface::class)->log($priority, $message, $extras);
    }
}
// Add global translation helper
if (!function_exists('_g')) {
    function _g($entity)
    {
        return \LeKoala\Base\i18n\BaseI18n::globalTranslation($entity);
    }
}


if (Director::isDev()) {
    error_reporting(-1);
    ini_set('display_errors', true);

    // Enable IDEAnnotator
    if (!empty($_SERVER['SERVER_NAME']) &&
        in_array(substr($_SERVER['SERVER_NAME'], strrpos($_SERVER['SERVER_NAME'], '.') + 1), ['dev', 'local', 'localhost'])) {
        \SilverStripe\Core\Config\Config::modify()->set('SilverLeague\IDEAnnotator\DataObjectAnnotator', 'enabled', true);
    }
}

// When running tests, use SQLite3
// @link https://docs.silverstripe.org/en/4/developer_guides/testing/
// Currently, some issue when running test, see pull request
// @link https://github.com/silverstripe/silverstripe-sqlite3/pull/43
if (Director::is_cli()) {
    if (strpos($_SERVER['PHP_SELF'], 'phpunit/') !== false) {
        global $databaseConfig;
        if (class_exists(\SilverStripe\SQLite\SQLite3Database::class)) {
            $databaseConfig['type'] = 'SQLite3Database';
            $databaseConfig['path'] = ':memory:';
        }
    }
}

// If php runs under cgi, http auth might not work by default. Don't forget to update htaccess
// with the following lines:
//
// # Enable HTTP Basic authentication workaround for PHP running in CGI mode
// RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
//
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    if (isset($_SERVER['HTTP_AUTHORIZATION']) && (strlen($_SERVER['HTTP_AUTHORIZATION']) > 0)) {
        list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
        if (strlen($_SERVER['PHP_AUTH_USER']) == 0 || strlen($_SERVER['PHP_AUTH_PW']) == 0) {
            unset($_SERVER['PHP_AUTH_USER']);
            unset($_SERVER['PHP_AUTH_PW']);
        }
    }
}

// Add styles selector, if you have an editor.css, styles will be use
// @link https://docs.silverstripe.org/en/4/developer_guides/customising_the_admin_interface/typography/
\SilverStripe\Forms\HTMLEditor\TinyMCEConfig::get('cms')
    ->addButtonsToLine(1, 'styleselect')
    ->setOption('statusbar', false)
    ->setOption('importcss_append', true);
