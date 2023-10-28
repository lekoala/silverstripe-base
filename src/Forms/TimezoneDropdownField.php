<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\ArrayLib;

/**
 * A timezone dropdown
 * @link https://www.php.net/manual/en/timezones.others.php
 * @link https://www.timeanddate.com/time/zones/
 */
class TimezoneDropdownField extends FastDropdownField
{
    // in yml, it needs to be name/value like so
    // - name: "Gulf Standard Time (GST)"
    //   value: "Asia/Dubai"
    private static $aliases = [
        // "Gulf Standard Time (GST)" => "Asia/Dubai",
        // "Central European Time (CET)" => "Europe/Brussels",
        // "Atlantic Standard Time (AST)" => "America/Blanc-Sablon",
        // "Eastern Standard Time (EST)" => "America/Panama",
        // "Central Standard Time (CST)" => "America/Regina",
        // "Mountain Standard Time (MST)" => "America/Phoenix",
        // "Pacific Standard Time (PST)" => "America/Los_Angeles",
    ];

    /**
     * @param string $name The field name
     * @param string $title The field title
     * @param array|ArrayAccess $source A map of the dropdown items
     * @param mixed $value The current value
     */
    public function __construct($name, $title = null, $source = array(), $value = null)
    {
        if (empty($source)) {
            $source = ArrayLib::valuekey(timezone_identifiers_list());
            $aliases = $this->config()->aliases ?? [];
            if (!empty($aliases)) {
                $normalizedAliases = [];
                foreach ($aliases as $k => $v) {
                    // Add a prefix to avoid clashes with regular value
                    if (is_int($k)) {
                        $normalizedAliases[$v['name']] = '_' . $v['value'];
                    } else {
                        $normalizedAliases[$k] = '_' . $v;
                    }
                }

                $source = array_flip($normalizedAliases) + $source;
            }
        }
        parent::__construct($name, $title, $source, $value);
    }

    public function normalizedAliases()
    {
        $aliases = $this->config()->aliases ?? [];
        if (!empty($aliases)) {
            $normalizedAliases = [];
            foreach ($aliases as $k => $v) {
                if (is_int($k)) {
                    $normalizedAliases[$v['name']] = $v['value'];
                } else {
                    $normalizedAliases[$k] = $v;
                }
            }

            return $normalizedAliases;
        }
        return [];
    }

    public function dataValue()
    {
        return ltrim(parent::dataValue(), '_');
    }
}
