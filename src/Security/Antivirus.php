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
 * You must install lib yourself in your project
 *
 * @link https://github.com/jonjomckay/quahog
 * @link https://docs.clamav.net/
 */
class Antivirus
{
    public static function isConfigured()
    {
        return self::getDaemonPath() != false && class_exists(Client::class);
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
        if (!self::isConfigured()) {
            return false;
        }
        $pid = self::getPidFile();
        if ($pid && is_file($pid)) {
            return true;
        }
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
        $scanner = self::getScanner();
        $result = $scanner->scanFile($path);

        if ($result->isFound()) {
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

        $scanner = self::getScanner();
        $result = $scanner->scanFile($path);

        if ($result->isFound()) {
            unlink($path);
            if ($file->ID) {
                $file->delete();
            }
            throw new Exception("A virus has been detected and removed");
        }
    }
}
