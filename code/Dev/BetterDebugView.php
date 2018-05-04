<?php

namespace LeKoala\Base\Dev;

use SilverStripe\Dev\DebugView;

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
        $ide_link = str_replace(['{file}', '{line}'], [$file, $line], $placeholder);
        $link = "<a href=\"$ide_link\">$file:$line</a>";
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

}
