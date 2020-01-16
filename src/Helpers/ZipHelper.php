<?php

namespace LeKoala\Base\Helpers;

use Exception;
use ZipArchive;
use SilverStripe\Assets\FileNameFilter;

class ZipHelper
{
    /**
     * Zip error message
     *
     * @param int $code
     * @return string
     */
    public static function getErrorMessage($code)
    {
        switch ($code) {
            case 0:
                return 'No error';
            case 1:
                return 'Multi-disk zip archives not supported';
            case 2:
                return 'Renaming temporary file failed';
            case 3:
                return 'Closing zip archive failed';
            case 4:
                return 'Seek error';
            case 5:
                return 'Read error';
            case 6:
                return 'Write error';
            case 7:
                return 'CRC error';
            case 8:
                return 'Containing zip archive was closed';
            case 9:
                return 'No such file';
            case 10:
                return 'File already exists';
            case 11:
                return 'Can\'t open file';
            case 12:
                return 'Failure to create temporary file';
            case 13:
                return 'Zlib error';
            case 14:
                return 'Malloc failure';
            case 15:
                return 'Entry has been changed';
            case 16:
                return 'Compression method not supported';
            case 17:
                return 'Premature EOF';
            case 18:
                return 'Invalid argument';
            case 19:
                return 'Not a zip archive';
            case 20:
                return 'Internal error';
            case 21:
                return 'Zip archive inconsistent';
            case 22:
                return 'Can\'t remove file';
            case 23:
                return 'Entry has been deleted';
            default:
                return 'An unknown error has occurred(' . intval($code) . ')';
        }
    }

    /**
     * Unzip a file to a directory using ZipArchive
     *
     * @param string $file
     * @param string $dir
     * @return void
     */
    public static function unzipTo($file, $dir)
    {
        $ZipArchive = new ZipArchive;
        $res = $ZipArchive->open($file);
        if ($res === true) {
            $ZipArchive->extractTo($dir);
            $ZipArchive->close();
        } else {
            throw new Exception("Failed to unzip $file : " . self::getErrorMessage($res));
        }
    }

    /**
     * Serve the content of the given file
     *
     * @param string $file
     * @param string $title
     * @param boolean $unlink
     * @return void
     */
    public static function outputFile($file, $title, $unlink = false)
    {
        $filter = new FileNameFilter;

        $title = $filter->filter($title) . '.zip';

        if (ob_get_contents())
            ob_end_clean();

        if (!is_readable($file)) {
            die("File $file is not readable");
        }

        if (!headers_sent()) {
            // Redirect output to a client’s web browser
            header("Content-Type: application/zip");
            header('Content-Disposition: attachment;filename="' . $title . '"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            header("Content-Length: " . filesize($file));
        }

        $result = readfile($file);
        if (!$result) {
            die('Failed to read file');
        }
        if ($unlink) {
            unlink($file);
        }
        exit();
    }

    protected static function isWindows()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Zip content of a file using ZipArchive
     *
     * @param string $filename
     * @param string $content
     * @return string
     */
    public static function zipContent($filename, $content)
    {
        $tmp = tempnam('tmp', 'zip');

        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString($filename, $content);
        $zip->close();

        return $tmp;
    }

    /**
     * Zip a single file or directory using system zip utility. This allows setting a password on the file.
     *
     * @param string $infile
     * @param string $outfile
     * @param string $password
     * @return boolean|string
     */
    public static function zipFile($infile, $outfile = null, $password = null)
    {
        if (!$outfile) {
            $dir = dirname($infile);
            $name = pathinfo($infile, PATHINFO_FILENAME);
            $outfile = $dir . '/' . $name . '.zip';
        }

        if (is_file($outfile)) {
            unlink($outfile);
        }

        $args = '-j';
        if (is_dir($infile)) {
            $args .= ' -r';
        }
        if ($password) {
            $args .= ' -P ' . $password;
        }

        // 2>&1 => capture error msg
        $cmd = "zip $args $outfile $infile 2>&1";
        // on windows we can find a zip executable
        if (self::isWindows()) {
            //"command"   "switches"   "full_path_archive_name"   "full_path_file_name"
            $cmd = "7z a \"$outfile\" \"$infile\*\"";
            $cmd = "\"C:\\Program Files\\7-Zip\\7z.exe\" a \"$outfile\" \"$infile\*\"";
        }

        $return = null;
        @exec($cmd, $return);

        if (!$return) {
            throw new Exception("Empty result when creating archive with " . $cmd);
        }

        $line = $return[0];
        $fullReturn = implode("\n", $return);
        if (strpos($line, 'adding:') !== false || strpos($fullReturn, 'Everything is Ok') !== false) {
            return $outfile;
        }
        if (empty($line)) {
            $line = $fullReturn;
        }
        throw new Exception("Unable to create archive : " . $line);
    }
}
