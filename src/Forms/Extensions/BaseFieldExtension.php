<?php

namespace LeKoala\Base\Forms\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;

/**
 * Utilities for fields
 */
class BaseFieldExtension extends Extension
{
    /**
     * Prevent ugly autocomplete to fill in emails and passwords
     * in your form fields
     *
     * @return void
     */
    public function preventAutocomplete()
    {
        $this->owner->setAttribute('autocomplete', 'new-password');
        $this->owner->setAttribute('readonly', 'readonly');
        $this->owner->setAttribute('onfocus', "this.removeAttribute('readonly');");
    }

    /**
     * Get tooltip (title attr)
     *
     * @return string
     */
    public function getTooltip()
    {
        return $this->owner->getAttribute('title');
    }

    /**
     * Set tooltip (as title attr)
     *
     * @param string $value
     * @return FormField
     */
    public function setTooltip($value)
    {
        $this->owner->setAttribute('title', $value);
        $this->owner->setAttribute('data-toggle', 'tooltip');
        //TODO: figure out why the javascript is not properly triggered
        return $this->owner;
    }
}
