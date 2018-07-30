<?php

use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\Base\Forms\GridField\GridFieldTableButton;

/**
 * A button that does nothing except reloading the data
 *
 * @author Koala
 */
class GridFieldRefreshButton extends GridFieldTableButton
{
    protected $noAjax = false;
    protected $fontIcon = 'spinner';

    public function getButtonLabel()
    {
        return 'Refresh';
    }

    /**
     */
    public function handle(GridField $gridfield, Controller $controller)
    {
    }
}
