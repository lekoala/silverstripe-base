<?php

namespace LeKoala\Base\Helpers;

use Exception;

class JsonHelper
{
    /**
     * @param mixed $data
     * @param int $flags
     * @return string
     */
    public static function encode($data, int $flags = 0): string
    {
        $result = json_encode($data, $flags);
        if ($result === false) {
            throw new Exception(json_last_error_msg());
        }
        return $result;
    }

    /**
     * @param mixed $json
     * @param int $flags
     * @return mixed
     */
    public static function decode($json, int $flags = 0)
    {
        $result = json_decode($json, null, 512, $flags);
        if (!$result) {
            throw new Exception(json_last_error_msg());
        }
        return $result;
    }

    /**
     * @param mixed $json
     * @param int $flags
     * @return array<mixed>
     */
    public static function decodeAssoc($json, int $flags = 0)
    {
        $result = json_decode($json, true, 512, $flags);
        if (!$result) {
            throw new Exception(json_last_error_msg());
        }
        return $result;
    }
}
