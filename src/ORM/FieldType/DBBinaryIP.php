<?php

namespace LeKoala\Base\ORM\FieldType;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;

/**
 * A field to store ip address in binary formats
 *
 * @link https://stackoverflow.com/questions/22636912/store-both-ipv4-and-ipv6-address-in-a-single-column
 * @link https://www.php.net/manual/en/function.inet-pton.php
 * @link https://www.php.net/manual/en/function.inet-ntop.php
 * @link https://github.com/S1lentium/IPTools/blob/master/src/IP.php
 */
class DBBinaryIP extends DBField
{
    const IP_V4 = 'IPv4';
    const IP_V6 = 'IPv6';

    const IP_V4_MAX_PREFIX_LENGTH = 32;
    const IP_V6_MAX_PREFIX_LENGTH = 128;

    const IP_V4_OCTETS = 4;
    const IP_V6_OCTETS = 16;

    public function requireField()
    {
        // Use direct sql statement here
        $sql = "binary(16)";
        DB::require_field($this->tableName, $this->name, $sql);
    }

    /**
     * @return string A readable ip address like 127.0.0.1
     */
    public function Nice()
    {
        if (!$this->value) {
            return $this->nullValue();
        }
        return inet_ntop($this->value);
    }

    /**
     * @return string
     */
    public function BinValue()
    {
        $binary = array();
        foreach (unpack('C*', $this->value) as $char) {
            $binary[] = str_pad(decbin($char), 8, '0', STR_PAD_LEFT);
        }

        return implode($binary);
    }

    /**
     * @return string
     */
    public function HexValue()
    {
        return bin2hex($this->value);
    }

    /**
     * @return string
     */
    public function LongValue()
    {
        $long = 0;
        if ($this->getVersion() === self::IP_V4) {
            $long = sprintf('%u', ip2long(inet_ntop($this->value)));
        } else {
            $octet = self::IP_V6_OCTETS - 1;
            foreach ($chars = unpack('C*', $this->value) as $char) {
                $long = bcadd($long, bcmul($char, bcpow(256, $octet--)));
            }
        }

        return $long;
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return false;
    }

    public function nullValue()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $version = '';

        if (filter_var(inet_ntop($this->value), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $version = self::IP_V4;
        } elseif (filter_var(inet_ntop($this->value), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $version = self::IP_V6;
        }

        return $version;
    }

    /**
     * @return int
     */
    public function getMaxPrefixLength()
    {
        return $this->getVersion() === self::IP_V4
            ? self::IP_V4_MAX_PREFIX_LENGTH
            : self::IP_V6_MAX_PREFIX_LENGTH;
    }

    /**
     * @return int
     */
    public function getOctetsCount()
    {
        return $this->getVersion() === self::IP_V4
            ? self::IP_V4_OCTETS
            : self::IP_V6_OCTETS;
    }

    /**
     * @return string
     */
    public function getReversePointer()
    {
        if ($this->getVersion() === self::IP_V4) {
            $reverseOctets = array_reverse(explode('.', $this->Nice()));
            $reversePointer = implode('.', $reverseOctets) . '.in-addr.arpa';
        } else {
            $unpacked = unpack('H*hex', $this->value);
            $reverseOctets = array_reverse(str_split($unpacked['hex']));
            $reversePointer = implode('.', $reverseOctets) . '.ip6.arpa';
        }

        return $reversePointer;
    }

    public function prepValueForDB($value)
    {
        if (!$value) {
            return $this->nullValue();
        }

        // String ip contains dots
        if (strpos($value, '.') !== false) {
            return inet_pton($value);
        }
        // Strlen 16 = already binary
        if (strlen($value) === 16) {
            return $value;
        }
        throw new Exception("$value seems an invalid ip value");
    }
}
