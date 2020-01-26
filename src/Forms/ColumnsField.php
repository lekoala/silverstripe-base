<?php

namespace LeKoala\Base\Forms;

use InvalidArgumentException;
use SilverStripe\Forms\FieldGroup;

/**
 * Display fields in column
 *
 * @link https://getbootstrap.com/docs/4.4/layout/grid/
 * @link http://sassflexboxgrid.com/
 */
class ColumnsField extends FieldGroup
{
    /**
     * @config
     * @var boolean
     */
    private static $autosize = true;
    /**
     * @config
     * @var string
     */
    private static $column_class = 'col';

    /**
     * @var string
     */
    protected $breakpoint = 'md';

    /**
     * @var array
     */
    protected $columnSizes = [];


    public function __construct($children = null)
    {
        parent::__construct($children);
        $this->setColumnCount(count($this->children));
    }

    /**
     * Called in template
     * <div class="$Up.ColumnClass($Pos) $FirstLast $EvenOdd">
     * $FieldHolder
     * </div>
     *
     * @param int $pos
     * @return string
     */
    public function ColumnClass($pos)
    {
        $col_class = self::config()->column_class;
        $class = $col_class;
        if ($this->breakpoint) {
            $class .= '-' . $this->breakpoint;
            if (isset($this->columnSizes[$pos])) {
                $class .= '-' . $this->columnSizes[$pos];
            } elseif (self::config()->autosize) {
                $autoSize = round(12 / count($this->children));
                $class .= '-' . $autoSize . ' ' . $col_class . '-xs-12';
            }
        }
        return $class;
    }

    /**
     * Get the value of breakpoint
     * @return string
     */
    public function getBreakpoint()
    {
        return $this->breakpoint;
    }

    /**
     * Set the value of breakpoint
     * @return $this
     */
    public function setBreakpoint($breakpoint)
    {
        $this->breakpoint = $breakpoint;

        return $this;
    }

    /**
     * Get the value of columnSizes
     * @return array
     */
    public function getColumnSizes()
    {
        return $this->columnSizes;
    }

    /**
     * Set size of columns based on their position
     *
     * Eg: [1 => 4, 2 => 8]
     *
     * @param array $columnSizes An position based array of sizes to assign to your columns
     * @return $this
     */
    public function setColumnSizes($columnSizes)
    {
        if (!is_array($columnSizes)) {
            throw new InvalidArgumentException("columnSizes should be an array, Eg: [1 => 4, 2 => 8]");
        }
        $this->columnSizes = $columnSizes;
        return $this;
    }

    /**
     * @param int $col
     * @return string
     */
    public function getColumnSize($col)
    {
        if (isset($this->columnSizes[$col])) {
            return $this->columnSizes[$col];
        }
    }

    /**
     * @param int $col
     * @param int $size
     * @return $this
     */
    public function setColumnSize($col, $size)
    {
        $this->columnSizes[$col] = $size;
        return $this;
    }

    public function Field($properties = array())
    {
        $result = parent::Field($properties);
        return $result;
    }

    public function FieldHolder($properties = array())
    {
        $result = parent::FieldHolder($properties);
        return $result;
    }
}
