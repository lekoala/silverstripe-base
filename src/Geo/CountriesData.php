<?php

namespace LeKoala\Base\Geo;

/**
 * @author Koala
 */
class CountriesData
{
    const SHORT_NAME = 'ShortName';
    const OFFICIAL_NAME = 'OfficialName';
    const ISO3 = 'ISO3';
    const ISO2 = 'ISO2';
    const UNI = 'UNI';
    const UNDP = 'UNDP';
    const FAOSTAT = 'FAOSTAT';
    const GAUL = 'GAUL';

    /**
     * @var string
     */
    private $file;
    /**
     * @var array
     */
    private $data;
    /**
     * @param string $file
     */
    public function __construct($file = null)
    {
        if ($file) {
            $this->setFile($file);
        } else {
            $this->setFile(dirname(dirname(__DIR__)) . '/resources/Geo/countries.csv');
        }
    }
    /**
     * @param string $file
     * @return $this
     */
    public static function getInstance($file = null)
    {
        return new self($file);
    }
    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }
    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }
    private function loadData()
    {
        if ($this->data) {
            return;
        }
        if (!is_file($this->file)) {
            throw new Exception("File {$this->file} is not valid");
        }
        if (!is_readable($this->file)) {
            throw new Exception("File {$this->file} is not readable");
        }
        $file = fopen($this->file, "r");
        $arr = array();
        $headers = fgetcsv($file);
        while (!feof($file)) {
            $arr[] = array_combine($headers, fgetcsv($file));
        }
        fclose($file);
        $this->data = $arr;
    }
    /**
     * Get the list of all countries
     *
     * @return array
     */
    public function getCountries()
    {
        $this->loadData();
        return $this->data;
    }
    /**
     * Convert a code to another
     *
     * @param string $code
     * @param string $from
     * @param string $to
     * @return string
     */
    public function convertCode($code, $from, $to)
    {
        if (!$code) {
            return false;
        }
        $countries = $this->getCountries();
        foreach ($countries as $country) {
            if ($country[$from] == $code) {
                return $country[$to];
            }
        }
        return false;
    }
    /**
     * Convert ISO2 to ISO3
     *
     * @param string $code
     * @return string
     */
    public function convertIso2ToIso3($code)
    {
        return $this->convertCode($code, self::ISO2, self::ISO3);
    }
    /**
     * Convert ISO2 to ISO3
     *
     * @param string $code
     * @return string
     */
    public function convertIso3ToIso2($code)
    {
        return $this->convertCode($code, self::ISO3, self::ISO2);
    }
    /**
     * Get a map of countries as key => value
     *
     * @param string $key
     * @param string $value
     * @return array
     */
    public function toMap($key = 'ISO2', $value = 'ShortName')
    {
        $arr = array();
        foreach ($this->getCountries() as $country) {
            $arr[$country[$key]] = $country[$value];
        }
        return $arr;
    }

    /**
     * Get the country list, using IntlLocales
     *
     * @return array
     */
    public static function getCountryList()
    {
        $intl = new IntlLocales;
        return $intl->getCountries();
    }
}
