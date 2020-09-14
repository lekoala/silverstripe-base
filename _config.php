<?php

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;

// Add a benchmark helper

if (!function_exists('bm')) {
    function bm($cb = null)
    {
        \LeKoala\Base\Dev\Benchmark::run($cb);
    }
}
// Add a debug helper
if (!function_exists('d')) {
    function d(...$args)
    {
        // Don't show on live
        if (Director::isLive()) {
            return;
        }

        $req = null;
        if (Controller::has_curr()) {
            $req = Controller::curr()->getRequest();
        }
        $debugView = \SilverStripe\Dev\Debug::create_debug_view($req);
        // Also show latest object in backtrace
        if (!Director::is_ajax()) {
            foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT) as $row) {
                if (!empty($row['object'])) {
                    $args[] = $row['object'];
                    break;
                }
            }
        }
        // Show args
        $i = 0;
        $output = [];
        foreach ($args as $val) {
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
        if (!is_string($message)) {
            $message = json_encode($message);
        }
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

// Timezone setting
$SS_TIMEZONE = Environment::getEnv('SS_TIMEZONE');
if ($SS_TIMEZONE) {
    if (!in_array($SS_TIMEZONE, timezone_identifiers_list())) {
        throw new Exception("Timezone $SS_TIMEZONE is not valid");
    }
    date_default_timezone_set($SS_TIMEZONE);
}

$SS_SERVERNAME = $_SERVER['SERVER_NAME'] ?? 'localhost';
if (Director::isDev()) {
    error_reporting(-1);
    ini_set('display_errors', true);

    // Enable IDEAnnotator
    if (in_array(substr($SS_SERVERNAME, strrpos($SS_SERVERNAME, '.') + 1), ['dev', 'local', 'localhost'])) {
        \SilverStripe\Core\Config\Config::modify()->set('SilverLeague\IDEAnnotator\DataObjectAnnotator', 'enabled', true);
        \SilverStripe\Core\Config\Config::modify()->merge('SilverLeague\IDEAnnotator\DataObjectAnnotator', 'enabled_modules', [
            'app'
        ]);
    }

    // Fixes https://github.com/silverleague/silverstripe-ideannotator/issues/122
    \SilverStripe\Core\Config\Config::modify()->set('SilverLeague\IDEAnnotator\Tests\Team', 'has_many', []);
}

// When running tests, use SQLite3
// @link https://docs.silverstripe.org/en/4/developer_guides/testing/
// Currently, some issue when running test, see pull request
// @link https://github.com/silverstripe/silverstripe-sqlite3/pull/43
if (Director::is_cli()) {
    if (isset($_SERVER['argv'][0]) && $_SERVER['argv'][0] == 'vendor/bin/phpunit') {
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
    ->addButtonsToLine(2, 'anchor')
    ->enablePlugins('anchor')
    ->setOption('statusbar', false)
    ->setOption('importcss_append', true);

// GraphQL performance
// See _config/controllers.yml
// @link https://github.com/silverstripe/silverstripe-graphql/issues/192
// if (Director::isLive()) {
//     \SilverStripe\GraphQL\Controller::remove_extension(\SilverStripe\GraphQL\Extensions\IntrospectionProvider::class);
// }
