<?php

namespace LeKoala\Base\Forms\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;

/**
 * Deprecated
 *
 * This is merged into BaseFieldExtension
 *
 * @property \LeKoala\Base\Forms\Extensions\TooltipExtension $owner
 */
class TooltipExtension extends Extension
{
    public function getTooltip()
    {
        return $this->owner->getAttribute('title');
    }
    public function setTooltip($value)
    {
        $this->owner->setAttribute('title', $value);
        $this->owner->setAttribute('data-toggle', 'tooltip');
        //TODO: figure out why the javascript is not properly triggered
    }
}
