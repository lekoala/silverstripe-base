<?php

namespace LeKoala\Base\Security;

use Exception;
use Socket\Raw\Factory;
use Xenolope\Quahog\Client;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Environment;
use LeKoala\Base\Extensions\BaseFileExtension;

/**
 * You must install lib yourself in your project or use exec
 *
 * @link https://github.com/jonjomckay/quahog
 * @link https://docs.clamav.net/
 */
class Antivirus
{
    public static function isPhpEnvSupported()
    {
        if (!self::useSocket() && !self::useExec()) {
            return false;
        }
        return true;
    }

    public static function useExec()
    {
        $res = exec(self::getExecPath() . ' --ping 10');
        if ($res === "PONG") {
            return true;
        }
        return false;
    }

    public static function useSocket()
    {
        return function_exists('socket_connect') && class_exists(Client::class);
    }

    public static function isConfigured()
    {
        if (self::getDaemonPath() || self::getExecPath()) {
            return true;
        }
        return false;
    }

    /**
     * Could be something like ANTIVIRUS_EXEC="C:\Program^ Files\ClamAV\clamdscan.exe" or clamdscan
     *
     * @return string
     */
    public static function getExecPath()
    {
        return Environment::getEnv('ANTIVIRUS_EXEC');
    }

    public static function getDaemonPath()
    {
        return Environment::getEnv('ANTIVIRUS_SOCKET');
    }

    public static function getPidFile()
    {
        return Environment::getEnv('ANTIVIRUS_PID_FILE');
    }

    /**
     * Check if env file is set
     * If pid file (if configured) exists
     * Otherwise, ping the client
     *
     * @return boolean
     */
    public static function isConfiguredAndWorking()
    {
        if (self::getExecPath() && self::useExec()) {
            return true;
        }

        // Check for pid file
        $pid = self::getPidFile();
        if ($pid && is_file($pid)) {
            return true;
        }
        // Try to init scanner
        try {
            $scanner = self::getScanner();
        } catch (Exception $e) {
            return false;
        }
        return $scanner->ping();
    }

    /**
     * @return Client
     */
    public static function getScanner()
    {
        // $socket = (new \Socket\Raw\Factory())->createClient('unix:///var/run/clamav/clamd.ctl'); # Using a UNIX socket
        // $socket = (new \Socket\Raw\Factory())->createClient('tcp://127.0.0.1:3310'); # Using a TCP socket
        $socket = (new Factory())->createClient(self::getDaemonPath());

        // Create a new instance of the Client
        $quahog = new Client($socket, 5, PHP_NORMAL_READ);

        return $quahog;
    }

    /**
     * @param string $path
     * @param File|BaseFileExtension $file
     * @return void
     */
    public static function scanFile($path, $file = null)
    {
        if (self::useExec()) {
            $res = shell_exec(self::getExecPath() . ' ' . $path);
            $virusFound = strpos($res, 'Infected files: 1') !== false;
        } else {
            $scanner = self::getScanner();
            $result = $scanner->scanFile($path);
            $virusFound = $result->isFound();
        }

        if ($virusFound) {
            unlink($path);
            if ($file && $file->ID) {
                $file->delete();
            }
            throw new Exception("A virus has been detected and removed");
        }
    }

    /**
     * @param File|BaseFileExtension $file
     * @return void
     */
    public static function scan($file)
    {
        if ($file instanceof Folder) {
            return;
        }

        $path = $file->getFullPath();

        if (self::useExec()) {
            $res = shell_exec(self::getExecPath() . ' ' . $path);
            $virusFound = strpos($res, 'Infected files: 1') !== false;
        } else {
            $scanner = self::getScanner();
            $result = $scanner->scanFile($path);
            $virusFound = $result->isFound();
        }

        if ($virusFound) {
            unlink($path);
            if ($file->ID) {
                $file->delete();
            }
            throw new Exception("A virus has been detected and removed");
        }
    }
}
