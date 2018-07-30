<?php
namespace LeKoala\Base\Forms\Bootstrap;

use SilverStripe\Forms\Tab as DefaultTab;

class Tab extends DefaultTab
{
    protected $selected = false;

    /**
     * Get the value of selected
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * Set the value of selected
     *
     * @return $this
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;

        return $this;
    }
}
