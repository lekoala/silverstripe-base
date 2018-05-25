<?php

namespace LeKoala\Base\Dev;

use SilverStripe\Dev\DebugView;
use SilverStripe\Dev\Backtrace;
use SilverStripe\Core\Environment;

class BetterDebugView extends DebugView
{
    /**
     * @config
     * @var string
     */
    private static $ide_placeholder = 'vscode://file/{file}:{line}:0';

    /**
     * @param string $file
     * @param string $line
     * @param string $text
     * @return string
     */
    public static function makeIdeLink($file, $line)
    {
        $placeholder = self::config()->ide_placeholder;

        // each dev can define their own settings
        $envPlaceholder = Environment::getEnv('IDE_PLACEHOLDER');
        if ($envPlaceholder) {
            $placeholder = $envPlaceholder;
        }

        $ide_link = str_replace(['{file}', '{line}'], [$file, $line], $placeholder);
        $shortname = basename($file);
        $link = "<a href=\"$ide_link\">$shortname:$line</a>";
        return $link;
    }

    /**
     * Render an error.
     *
     * @param string $httpRequest the kind of request
     * @param int $errno Codenumber of the error
     * @param string $errstr The error message
     * @param string $errfile The name of the soruce code file where the error occurred
     * @param int $errline The line number on which the error occured
     * @return string
     */
    public function renderError($httpRequest, $errno, $errstr, $errfile, $errline)
    {
        $errorType = isset(self::$error_types[$errno]) ? self::$error_types[$errno] : self::$unknown_error;
        $httpRequestEnt = htmlentities($httpRequest, ENT_COMPAT, 'UTF-8');
        if (ini_get('html_errors')) {
            $errstr = strip_tags($errstr);
        } else {
            $errstr = Convert::raw2xml($errstr);
        }

        $infos = self::makeIdeLink($errfile, $errline);

        $output = '<div class="header info ' . $errorType['class'] . '">';
        $output .= "<h1>[" . $errorType['title'] . '] ' . $errstr . "</h1>";
        $output .= "<h3>$httpRequestEnt</h3>";
        $output .= "<p>$infos</p>";
        $output .= '</div>';

        return $output;
    }

    /**
    * Render a call track
    *
    * @param  array $trace The debug_backtrace() array
    * @return string
    */
    public function renderTrace($trace)
    {
        $output = '<div class="info">';
        $output .= '<h3>Trace</h3>';
        $output .= self::get_rendered_backtrace($trace);
        $output .= '</div>';

        return $output;
    }

    /**
     * Render a backtrace array into an appropriate plain-text or HTML string.
     *
     * @param array $bt The trace array, as returned by debug_backtrace() or Exception::getTrace()
     * @return string The rendered backtrace
     */
    public static function get_rendered_backtrace($bt)
    {
        if (empty($bt)) {
            return '';
        }
        $result = '<ul>';
        foreach ($bt as $item) {
            if ($item['function'] == 'user_error') {
                $name = $item['args'][0];
            } else {
                $name = Backtrace::full_func_name($item, true);
            }
            $result .= "<li><b>" . htmlentities($name, ENT_COMPAT, 'UTF-8') . "</b>\n<br />\n";
            if (!isset($item['file']) || !isset($item['line'])) {
                $result .= isset($item['file']) ? htmlentities(basename($item['file']), ENT_COMPAT, 'UTF-8') : '';
                $result .= isset($item['line']) ? ":$item[line]" : '';
            } else {
                $result .= self::makeIdeLink($item['file'], $item['line']);
            }
            $result .= "</li>\n";
        }
        $result .= '</ul>';
        return $result;
    }
}
