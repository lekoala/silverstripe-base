<?php

namespace LeKoala\Base\Dev;

use Exception;
use SilverStripe\ORM\DB;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Backtrace;
use SilverStripe\Dev\DebugView;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\Parsers\SQLFormatter;
use SilverStripe\ORM\Connect\DatabaseException;
use LeKoala\Base\Helpers\DatabaseHelper;

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
     * @return string
     */
    public static function makeIdeLink($file, $line)
    {
        $shortname = basename($file);

        // does not make sense in testing or live
        if (!Director::isDev()) {
            return "$shortname:$line";
        }

        $placeholder = self::config()->ide_placeholder;

        // each dev can define their own settings
        $envPlaceholder = Environment::getEnv('IDE_PLACEHOLDER');
        if ($envPlaceholder) {
            $placeholder = $envPlaceholder;
        }

        $ide_link = str_replace(['{file}', '{line}'], [$file, $line], $placeholder);
        $link = "<a href=\"$ide_link\">$shortname:$line</a>";
        return $link;
    }

    /**
     * Similar to renderVariable() but respects debug() method on object if available
     *
     * @param mixed $val
     * @param array $caller
     * @param bool $showHeader
     * @param int $argumentIndex
     * @return string
     */
    public function debugVariable($val, $caller, $showHeader = true, $argumentIndex = 0)
    {
        // Get arguments name
        $args = $this->extractArgumentsName($caller['file'], $caller['line']);

        if ($showHeader) {
            $callerFormatted = $this->formatCaller($caller);
            $defaultArgumentName = is_int($argumentIndex) ? 'Debug' : $argumentIndex;
            $argumentName = $args[$argumentIndex] ?? $defaultArgumentName;

            // Sql trick
            if (strpos(strtolower($argumentName), 'sql') !== false && is_string($val)) {
                $text = DatabaseHelper::formatSQL($val);
            } else {
                $text = $this->debugVariableText($val);
            }

            $html = "<div style=\"background-color: white; text-align: left;\">\n<hr>\n"
                . "<h3>$argumentName <span style=\"font-size: 65%\">($callerFormatted)</span>\n</h3>\n"
                . $text
                . "</div>";

            if (Director::is_ajax()) {
                $html = strip_tags($html);
            }

            return $html;
        }
        return $this->debugVariableText($val);
    }

    /**
     * @param string $file
     * @param int $line
     * @return array
     */
    protected function extractArgumentsName($file, $line)
    {
        // Arguments passed to the function are stored in matches
        $src = file($file);
        $src_line = $src[$line - 1];
        preg_match("/d\((.+)\)/", $src_line, $matches);
        // Find all arguments, ignore variables within parenthesis
        $arguments = array();
        if (!empty($matches[1])) {
            $arguments = array_map('trim', preg_split("/(?![^(]*\)),/", $matches[1]));
        }
        return $arguments;
    }

    /**
     * Get debug text for this object
     *
     * Use symfony dumper if it exists
     *
     * @param mixed $val
     * @return string
     */
    public function debugVariableText($val)
    {
        // Empty stuff is tricky
        if (empty($val)) {
            $valtype = gettype($val);
            return "<em>(empty $valtype)</em>";
        }

        if (Director::is_ajax()) {
            // In ajax context, we can still use debug info
            if (is_object($val) && ClassInfo::hasMethod($val, 'debug')) {
                return $val->debug();
            }
        } else {
            // Otherwise, we'd rater a full and usable object dump
            if (function_exists('dump') && (is_object($val) || is_array($val))) {
                ob_start();
                dump($val);
                return ob_get_clean();
            }
        }
        return parent::debugVariableText($val);
    }

    /**
     * Formats the caller of a method
     *
     * Improve method by creating the ide link
     *
     * @param  array $caller
     * @return string
     */
    protected function formatCaller($caller)
    {
        $return = self::makeIdeLink($caller['file'], $caller['line']);
        if (!empty($caller['class']) && !empty($caller['function'])) {
            $return .= " - {$caller['class']}::{$caller['function']}()";
        }
        return $return;
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

    public function writeException(Exception $exception)
    {
        $infos = self::makeIdeLink($exception->getFile(), $exception->getLine());

        $output = '<div class="build error">';
        $output .= "<p><strong>" . get_class($exception) . "</strong> in $infos</p>";
        $message = $exception->getMessage();
        if ($exception instanceof DatabaseException) {
            $sql = $exception->getSQL();
            // Some database errors don't have sql
            if ($sql) {
                $parameters = $exception->getParameters();
                $sql = DB::inline_parameters($sql, $parameters);
                $formattedSQL = DatabaseHelper::formatSQL($sql);
                $message .= "<br/><br/>Couldn't run query:<br/>" . $formattedSQL;
            }
        }
        $output .= "<p>" . $message . "</p>";
        $output .= '</div>';

        $output .= $this->renderTrace($exception->getTrace());

        echo $output;
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
