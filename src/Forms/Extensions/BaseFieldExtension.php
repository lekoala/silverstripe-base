<?php

namespace LeKoala\Base\Forms\Extensions;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;

/**
 * Utilities for fields
 * @property FormField $owner
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
        if ($this->owner->getAttribute("data-bs-tooltip")) {
            return $this->owner->getAttribute("data-tooltip");
        }
        if ($this->owner->getAttribute("data-tooltip")) {
            return $this->owner->getAttribute("data-tooltip");
        }
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
        $curr = Controller::has_curr() ? Controller::curr() : null;
        if ($curr && $curr->UseBootstrap5()) {
            $this->owner->setAttribute('data-bs-toggle', 'tooltip');
        } else {
            $this->owner->setAttribute('data-toggle', 'tooltip');
        }
        return $this->owner;
    }

    /**
     * Set tooltip (appended to title)
     * NOTE: only works if using our custom FormField_holder
     * because title is escape by default
     *
     * @param string $value
     * @return FormField
     */
    public function setTooltipIcon($value)
    {
        $curr = Controller::has_curr() ? Controller::curr() : null;
        // Not working in cms
        if ($curr && $curr instanceof LeftAndMain) {
            return $this->setTooltip($value);
        }
        $this->owner->setAttribute('data-tooltip', $value);
        $title = $this->owner->Title();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);transform:;-ms-filter:"><path d="M12,2C6.486,2,2,6.486,2,12s4.486,10,10,10s10-4.486,10-10S17.514,2,12,2z M13,17h-2v-6h2V17z M13,9h-2V7h2V9z"></path></svg>';

        if ($curr && $curr->UseBootstrap5()) {
            $title .= " <span data-bs-title=\"$value\" data-bs-toggle=\"tooltip\">$svg</value>";
        } else {
            $title .= " <span data-title=\"$value\" data-toggle=\"tooltip\">$svg</value>";
        }
        $this->owner->setTitle($title);
        return $this->owner;
    }

    public function getPlaceholderAttr()
    {
        return $this->owner->getAttribute("placeholder");
    }

    public function setPlaceholderAttr($v)
    {
        return $this->owner->setAttribute("placeholder", $v);
    }
}
