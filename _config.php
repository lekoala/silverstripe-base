<?php

use SilverStripe\Control\Director;

if (!function_exists('bm')) {
    function bm($cb = null)
    {
        \LeKoala\Dev\Benchmark::run($cb);
    };
}

if (Director::isDev()) {
    error_reporting(-1);
    ini_set('display_errors', true);
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
