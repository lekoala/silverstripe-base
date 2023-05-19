<?php

namespace LeKoala\Base\Security;

use Exception;
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
        if (!self::useSocket() && !self::pingExec()) {
            return false;
        }
        return true;
    }

    public static function pingExec()
    {
        $res = exec(self::getExecPath() . ' --ping 10');
        if ($res === "PONG") {
            return true;
        }
        return false;
    }

    public static function useSocket()
    {
        return function_exists('socket_connect') && class_exists(\Xenolope\Quahog\Client::class);
    }

    public static function isConfigured()
    {
        if (self::getDaemonPath() || self::getExecPath()) {
            return true;
        }
        return false;
    }

    public static function socketOrExec()
    {
        if (self::useSocket() && self::getDaemonPath()) {
            return 'socket';
        }
        if (self::getExecPath()) {
            return 'exec';
        }
        return 'none';
    }

    /**
     * Could be something like ANTIVIRUS_EXEC="C:\Program^ Files\ClamAV\clamdscan.exe" or clamdscan
     * You may need "clamdscan --stream" for it to work properly on temp files
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
        // Check pong
        if (self::getExecPath() && self::pingExec()) {
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
        if (!class_exists(\Socket\Raw\Factory::class) || !class_exists(\Xenolope\Quahog\Client::class)) {
            throw new Exception("Missing libs");
        }

        // $socket = (new \Socket\Raw\Factory())->createClient('unix:///var/run/clamav/clamd.ctl'); # Using a UNIX socket
        // $socket = (new \Socket\Raw\Factory())->createClient('tcp://127.0.0.1:3310'); # Using a TCP socket
        $socket = (new \Socket\Raw\Factory())->createClient(self::getDaemonPath());

        // Create a new instance of the Client
        $quahog = new \Xenolope\Quahog\Client($socket, 5, PHP_NORMAL_READ);

        return $quahog;
    }

    /**
     * @param string $path
     * @param File|BaseFileExtension $file
     * @return void
     */
    public static function scanFile($path, $file = null)
    {
        $virusFound = false;

        if (!$path && $file) {
            $path = $file->getFullPath();
        }

        if (self::getExecPath()) {
            // $res = shell_exec(self::getExecPath() . ' ' . escapeshellarg($path));
            $res = shell_exec(self::getExecPath() . ' ' . $path);
            if ($res === null || $res === false) {
                throw new Exception("Could not run virus scanner using: " . self::getExecPath() . ' ' . $path);
            }
            if (strpos($res, 'Total errors: 0') !== false) {
                throw new Exception(self::getExecPath() . ' ' . $path . "\nResult:\n" . $res);
            }
            $virusFound = strpos($res, 'Infected files: 1') !== false;
        } elseif (self::getDaemonPath()) {
            $scanner = self::getScanner();
            $result = $scanner->scanFile($path);
            if ($result->hasFailed()) {
                throw new Exception($result->getReason() . ': ' . $path);
            }
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
}
