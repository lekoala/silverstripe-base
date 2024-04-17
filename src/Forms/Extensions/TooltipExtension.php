<?php

namespace LeKoala\Base\Forms\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;
use SilverStripe\Control\Controller;

/**
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
        $curr = Controller::has_curr() ? Controller::curr() : null;
        if ($curr && $curr->hasMethod('UseBootstrap5') && $curr->UseBootstrap5()) {
            $this->owner->setAttribute('data-bs-toggle', 'tooltip');
        } else {
            $this->owner->setAttribute('data-toggle', 'tooltip');
        }
    }
}
