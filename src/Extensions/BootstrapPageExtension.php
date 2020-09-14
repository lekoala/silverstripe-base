<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\SS_List;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Cookie;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataExtension;

/**
 * Not enabled by default please use the following config
 *
 * SilverStripe\CMS\Model\SiteTree:
 *   extensions:
 *     - LeKoala\Base\Extensions\BootstrapPageExtension
 * LeKoala\Base\View\Alertify:
 *   theme: 'bootstrap'
 *
 * @property \AboutPage|\AvailableSpacesPage|\HomePage|\Page|\VisionPage|\PortfolioPage|\LeKoala\Base\Blocks\BlocksPage|\LeKoala\Base\Contact\ContactPage|\LeKoala\Base\Faq\FaqPage|\LeKoala\Base\News\NewsPage|\LeKoala\Base\Privacy\CookiesRequiredPage|\LeKoala\Base\Privacy\PrivacyNoticePage|\LeKoala\Base\Privacy\TermsAndConditionsPage|\SilverStripe\ErrorPage\ErrorPage|\SilverStripe\CMS\Model\RedirectorPage|\SilverStripe\CMS\Model\SiteTree|\SilverStripe\CMS\Model\VirtualPage|\LeKoala\Base\Extensions\BootstrapPageExtension $owner
 */
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
            return 'active section';
        }
        return '';
    }
    /**
     * Gives a bootstrap alert
     *
     * @param string $Content
     * @param string $Type
     * @param boolean $Dismissible
     * @param string $ID
     * @return string
     */
    public function BootstrapAlert($Content, $Type = 'info', $Dismissible = true, $ID = null)
    {
        if ($ID === null && $Dismissible) {
            $ID = md5($Content);
        }
        if ($Dismissible && $this->BootstrapAlertDismissed($ID)) {
            return '';
        }
        $tpl = new SSViewer('Bootstrap/Alert');
        $data = new ArrayData([
            'Content' => $Content,
            'Type' => $Type,
            'Dismissible' => $Dismissible,
            'ID' => $ID
        ]);
        return $tpl->process($data);
    }
    /**
     * Check if a given alert is dismissed
     *
     * @param string $ID
     * @return bool
     */
    public function BootstrapAlertDismissed($ID)
    {
        $DismissedAlerts = Cookie::get('DismissedAlerts');
        if ($DismissedAlerts) {
            $Decoded = json_decode($DismissedAlerts);
            if (in_array($ID, $Decoded)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Give a bootstrap modal
     *
     * @param string $Content
     * @param string $Title
     * @param string $ID
     * @param SS_List $Actions
     * @return void
     */
    public function BootstrapModal($Content, $Title, $ID = null, $Actions = null)
    {
        if ($ID === null) {
            $ID = md5($Content);
        }
        $tpl = new SSViewer('Bootstrap/Modal');
        $data = new ArrayData([
            'Content' => $Content,
            'Title' => $Title,
            'Actions' => $Actions,
            'ID' => $ID
        ]);
        return $tpl->process($data);
    }
}
