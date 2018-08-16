<?php
namespace LeKoala\Base\Forms;

/**
 * A checkbox set where you can tick or untick all
 */
class AllCheckboxSetField extends BetterCheckboxSetField
{

    public function __construct($name, $title = null, $source = array(), $value = null)
    {
        parent::__construct($name, $title, $source, $value);
        $this->addExtraItemClass('form-check-inline');
    }
}
