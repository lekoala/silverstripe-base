<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;

class BootstrapPageExtension extends DataExtension
{
    /**
     * Return "" or "active" depending on if this is the {@link SiteTree::isCurrent()} current page.
     *
     * @return string
     */
    public function BootstrapLinkOrCurrent()
    {
        return $this->owner->isCurrent() ? 'active' : '';
    }

    /**
     * Return "" or "section" depending on if this is the {@link SiteTree::isSection()} current section.
     *
     * @return string
     */
    public function BootstrapLinkOrSection()
    {
        return $this->owner->isSection() ? 'section' : '';
    }

    /**
     * Return "", "active" or "section" depending on if this page is the current page, or not on the current page
     * but in the current section.
     *
     * @return string
     */
    public function BootstrapLinkingMode()
    {
        if ($this->owner->isCurrent()) {
            return 'active';
        } elseif ($this->owner->isSection()) {
            return 'section';
        }
        return '';
    }
}
