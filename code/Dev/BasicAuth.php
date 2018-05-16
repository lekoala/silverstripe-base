<?php

namespace LeKoala\Base\Dev;

use SilverStripe\Core\Environment;

class BasicAuth
{
    /**
     * A simple way to http protected a website (for staging for instance)
     * This is required because somehow the default mechanism shipped with SilverStripe is
     * not working properly
     * TODO: check if it is still necessary??
     *
     * @return void
     */
    public static function protect()
    {
        $user = Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME');
        $password = Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        $hasSuppliedCredentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
        if ($hasSuppliedCredentials) {
            $isNotAuthenticated = ($_SERVER['PHP_AUTH_USER'] != $user || $_SERVER['PHP_AUTH_PW'] != $password);
        } else {
            $isNotAuthenticated = true;
        }
        if ($isNotAuthenticated) {
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            exit;
        }
    }
}
