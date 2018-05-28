<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FieldGroup;

/**
 * @link https://getbootstrap.com/docs/4.0/layout/grid/
 */
class ColumnsField extends FieldGroup
{
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


    public function ColumnClass($pos)
    {
        $class = 'col';
        if ($this->breakpoint) {
            $class .= '-' . $this->breakpoint;
        }
        if (isset($this->columnSizes[$pos])) {
            $class .= '-' . $this->columnSizes[$pos];
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
     * @return  self
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
     * Set the value of columnSizes
     *
     * @return  self
     */
    public function setColumnSizes($columnSizes)
    {
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
     * @return self
     */
    public function setColumnSize($col, $size)
    {
        $this->columnSizes[$col] = $size;
        return $this;
    }
}
