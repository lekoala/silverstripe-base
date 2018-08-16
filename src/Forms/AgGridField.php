<?php
namespace LeKoala\Base\Forms;

use Exception;
use SilverStripe\View\Requirements;

/**
 * @link https://www.ag-grid.com/javascript-getting-started/
 */
class AgGridField extends JsonFormField
{
    use ConfigurableField;

    // Types
    const TYPE_TEXT = 'text';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_TEXTAREA = 'textarea';
    // Column keys
    const KEY_NAME = 'field';
    const KEY_HEADER = 'headerName';
    const KEY_VALUE = 'value';
    const KEY_TYPE = 'type';
    const KEY_CELL_EDITOR_PARAMS = 'cellEditorParams';
    // Editors
    // @link https://www.ag-grid.com/javascript-grid-cell-editing/#provided-cell-editors
    const EDITOR_TEXT = 'agTextCellEditor';
    const EDITOR_TEXT_POPUP = 'agPopupTextCellEditor';
    const EDITOR_TEXTAREA = 'agLargeTextCellEditor';
    const EDITOR_SELECT = 'agSelectCellEditor';

    /**
     * @var array
     */
    protected $columns = array();

    /**
     * Are the columns editable?
     *
     * If set to false, you need to pass editable => true in options
     * for your columns
     *
     * @var boolean
     */
    protected $columnsEditable = true;

    /**
     * @config
     * @var string
     */
    private static $theme = 'ag-theme-balham';

    /**
     * @config
     * @return array
     */
    private static $default_config = [
        'rowSelection' => 'multiple',
        'editType' => 'fullRow',
        'singleClickEdit' => true,
        'gridAutoHeight' => true,
    ];

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $this->mergeDefaultConfig();
    }

    public static function requirements()
    {
        $theme = self::config()->theme;

        Requirements::javascript('https://unpkg.com/ag-grid/dist/ag-grid.min.noStyle.js');
        Requirements::css('https://unpkg.com/ag-grid/dist/styles/ag-grid.css');
        Requirements::css('https://unpkg.com/ag-grid/dist/styles/' . $theme . '.css');

        Requirements::javascript('base/javascript/ModularBehaviour.js');
        Requirements::javascript('base/javascript/fields/AgGridField.js');
    }

    /**
     * Styles depend on config
     *
     * @link https://www.ag-grid.com/javascript-grid-width-and-height/
     * @return string
     */
    public function DefaultStyles()
    {
        if ($this->getGridAutoHeight()) {
            return 'width:100%;margin-bottom:.5rem';
        }
        return 'width:100%;height:300px;margin-bottom:.5rem';
    }

    protected function getEditorForType($type)
    {
        switch ($type) {
            case self::TYPE_TEXT:
                return self::EDITOR_TEXT;
            case self::TYPE_SELECT:
                return self::EDITOR_SELECT;
            case self::TYPE_TEXTAREA:
                return self::EDITOR_TEXTAREA;
        }
        return $type;
    }

    public function Field($properties = array())
    {
        $this->addExtraClass(self::config()->theme);
        $this->setAttribute('data-module', 'AgGridField');
        // Reference config in div
        $this->setAttribute('data-config', '#' . $this->ID() . 'Config');
        self::requirements();
        return parent::Field($properties);
    }

    /**
     * Because config can be very large, avoid storing it in an html attr
     *
     * @return string
     */
    public function JsonConfig()
    {
        $config = array_merge($this->config()->default_config, $this->config);
        $config['columnDefs'] = array_values($this->columns);

        $config['rowData'] = $this->value ?? [];

        return json_encode($config);
    }

    public function getEnableSorting()
    {
        return $this->getConfig('enableSorting');
    }

    /**
     * @link https://www.ag-grid.com/javascript-grid-width-and-height/
     * @param boolean $v
     * @return void
     */
    public function setEnableSorting($v = true)
    {
        return $this->setConfig('enableSorting', $v);
    }

    public function getEnableFilter()
    {
        return $this->getConfig('enableFilter');
    }

    public function setEnableFilter($v = true)
    {
        return $this->setConfig('enableFilter', $v);
    }

    public function getGridAutoHeight()
    {
        return $this->getConfig('gridAutoHeight');
    }

    public function setGridAutoHeight($v = true)
    {
        return $this->setConfig('gridAutoHeight', $v);
    }

    public function getAttributes()
    {
        $attrs = parent::getAttributes();
        unset($attrs['type']);
        unset($attrs['name']);
        return $attrs;
    }

    /**
     * @link https://www.ag-grid.com/javascript-grid-column-definitions/
     * @param string $name The data name
     * @param string $display The header name
     * @param string $type Type of editor
     * @param array $opts Other options to merge in
     * @return $this
     */
    public function addColumn($name, $display = null, $type = 'text', $opts = null)
    {
        if ($display === null) {
            $display = $name;
        }

        // Check for options for select
        if ($type == self::TYPE_SELECT) {
            if ($opts && !isset($opts[self::KEY_CELL_EDITOR_PARAMS])) {
                throw new Exception('Please define a "' . self::KEY_CELL_EDITOR_PARAMS . '" in options');
            }

            // Simplify declaration
            // Please note that associative array are not supported!
            if (!isset($opts[self::KEY_CELL_EDITOR_PARAMS]['values'])) {
                $givenOptions = $opts[self::KEY_CELL_EDITOR_PARAMS];
                $opts[self::KEY_CELL_EDITOR_PARAMS] = [
                    'values' => $givenOptions
                ];
            }
        }

        $baseOpts = array(
            self::KEY_NAME => $name,
            self::KEY_HEADER => $display,
        );

        if ($this->columnsEditable) {
            $baseOpts['editable'] = true;
        }

        if (!empty($opts)) {
            $baseOpts = array_merge($baseOpts, $opts);
        }

        // Set editor
        if (!empty($baseOpts['editable'])) {
            $baseOpts['cellEditor'] = $this->getEditorForType($type);
        }

        $this->columns[$name] = $baseOpts;
        return $this;
    }

    /**
     * Get column details

     * @param string $key
     * @return array
     */
    public function getColumn($key)
    {
        if (isset($this->columns[$key])) {
            return $this->columns[$key];
        }
    }

    /**
     * Set column details
     *
     * @param string $key
     * @param array $col
     * @return $this
     */
    public function setColumn($key, $col)
    {
        $this->columns[$key] = $col;
        return $this;
    }

    /**
     * Remove a column
     *
     * @param string $key
     */
    public function removeColumn($key)
    {
        unset($this->columns[$key]);
    }


    /**
     * Get the value of columns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set the value of columns
     *
     * @param array $columns
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Get for your columns
     *
     * @return boolean
     */
    public function getColumnsEditable()
    {
        return $this->columnsEditable;
    }

    /**
     * Set for your columns
     *
     * @param boolean $columnsEditable for your columns
     *
     * @return $this
     */
    public function setColumnsEditable(boolean $columnsEditable)
    {
        $this->columnsEditable = $columnsEditable;

        return $this;
    }
}
