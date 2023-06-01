<?php

namespace LeKoala\Base\Forms;

interface AjaxPoweredField
{
    public function getAjax();

    public function setAjax($url, $opts = []);

    /**
     * Define a callback that returns the results as a map of id => title
     *
     * @param string $class
     * @param string|array $where
     * @return $this
     */
    public function setAjaxWizard($class, $where = null);

    /**
     * Get ajax where
     *
     * @return string
     */
    public function getAjaxWhere();

    /**
     * Set ajax where
     *
     * @param string $ajaxWhere
     * @return $this
     */
    public function setAjaxWhere($ajaxWhere);

    /**
     * Get ajax class
     *
     * @return string
     */
    public function getAjaxClass();

    /**
     * Set ajax class
     *
     * @param string $ajaxClass  Ajax class
     * @return $this
     */
    public function setAjaxClass(string $ajaxClass);

    /**
     * @return boolean
     */
    public function isAjax();
}
