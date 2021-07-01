<?php

namespace LeKoala\Base\Forms;

/**
 * If you want to have a default_config, it's up to you to set
 * it in the constructor of your classes (by calling mergeDefaultConfig)
 */
trait ConfigurableField
{
    /**
     * Config array
     *
     * @var array
     */
    protected $config = [];

    /**
     * Get a config key value
     *
     * @param string $key
     * @return string
     */
    public function getConfig($key)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
    }

    /**
     * Get the config (always as json object)
     * @return string
     */
    public function getConfigAsJson()
    {
        if (empty($this->config)) {
            return '{}';
        }
        $json = json_encode($this->config);
        return $json;
    }

    /**
     * Get the config as a js object
     * @param array $escape
     * @return string
     */
    public function getConfigAsJs($escape = [])
    {
        if (empty($this->config)) {
            return '{}';
        }
        $json = json_encode($this->config);
        foreach ($escape as $fct) {
            $json = str_replace('"' . $fct . '"', $fct, $json);
        }
        return $json;
    }

    /**
     * Set a config value
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setConfig($key, $value)
    {
        if ($value !== null) {
            $this->config[$key] = $value;
        } else {
            unset($this->config[$key]);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function readConfig()
    {
        return $this->config;
    }

    /**
     * Merge default_config into config
     * @return void
     */
    public function mergeDefaultConfig()
    {
        $this->config = array_merge(self::config()->default_config, $this->config);
    }

    /**
     * @return $this
     */
    public function clearConfig()
    {
        $this->config = [];
        return $this;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function replaceConfig($config)
    {
        $this->config = $config;
        return $this;
    }
}
