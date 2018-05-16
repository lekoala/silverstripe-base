<?php
namespace LeKoala\Base\Forms\Extensions;

use SilverStripe\Core\Extension;

/**
 * Class \LeKoala\Base\Forms\Extensions\TooltipExtension
 *
 * @property \SilverStripe\Forms\FormField|\LeKoala\Base\Forms\Extensions\TooltipExtension $owner
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
