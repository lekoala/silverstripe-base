<?php
namespace LeKoala\Base\Actions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\DataExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Forms\CmsInlineFormAction;
use LeKoala\Base\Helpers\SilverStripeIcons;
use SilverStripe\Admin\CMSProfileController;

/**
 * Class \LeKoala\Base\Actions\DataObjectActionsExtension
 *
 * Maybe some day we don't have to do it ourself...
 *
 * @link https://github.com/silverstripe/silverstripe-cms/issues/2047
 * @property \LeKoala\Base\Blocks\Block|\LeKoala\Base\News\NewsCategory|\LeKoala\Base\News\NewsItem|\LeKoala\Base\Security\MemberAudit|\LeKoala\Base\Tags\Tag|\TimelineItem|\PortfolioCategory|\PortfolioItem|\SilverStripe\Assets\File|\SilverStripe\Assets\Image|\SilverStripe\SiteConfig\SiteConfig|\SilverStripe\Versioned\ChangeSetItem|\SilverStripe\CMS\Model\SiteTree|\SilverStripe\Security\Group|\SilverStripe\Security\Member|\LeKoala\Base\Actions\DataObjectActionsExtension $owner
 */
class DataObjectActionsExtension extends DataExtension
{
    /**
     * @link https://docs.silverstripe.org/en/3/developer_guides/customising_the_admin_interface/how_tos/extend_cms_interface/
     * @param FieldList $actions
     * @return void
     */
    public function updateCMSActions(FieldList $actions)
    {
        // Pages don't need to be improved
        if ($this->owner instanceof SiteTree) {
            return;
        }
        // SiteConfig doesn't need to be improved
        if ($this->owner instanceof SiteConfig) {
            return;
        }
        // Not implemented in CMSProfileController
        $ctrl = Controller::curr();
        if ($ctrl instanceof CMSProfileController) {
            return;
        }

        if ($this->owner->canEdit()) {
            if ($this->owner->ID) {
                $label = _t('DataObjectActionsExtension.SAVEANDCLOSE', 'Save and Close');
            } else {
                $label = _t('DataObjectActionsExtension.CREATEANDCLOSE', 'Create and Close');
            }
            $saveAndClose = new FormAction('doSaveAndClose', $label);
            $saveAndClose->addExtraClass('btn-primary');
            $saveAndClose->addExtraClass('font-icon-' . SilverStripeIcons::ICON_LEVEL_UP);
            $saveAndClose->setUseButtonTag(true);
            $actions->push($saveAndClose);
        }

        // Next/prev
        if ($this->owner->ID && $this->owner->hasMethod('PrevRecord') && $this->owner->PrevRecord()) {
            $doSaveAndPrev = new FormAction('doSaveAndPrev', 'Save and Previous');
            $doSaveAndPrev->addExtraClass('btn-primary');
            $doSaveAndPrev->addExtraClass('font-icon-' . SilverStripeIcons::ICON_ANGLE_DOUBLE_LEFT);
            $doSaveAndPrev->setUseButtonTag(true);
            $actions->push($doSaveAndPrev);
        }
        if ($this->owner->ID && $this->owner->hasMethod('NextRecord') && $this->owner->NextRecord()) {
            $doSaveAndNext = new FormAction('doSaveAndNext', 'Save and Next');
            $doSaveAndNext->addExtraClass('btn-primary');
            $doSaveAndNext->addExtraClass('font-icon-' . SilverStripeIcons::ICON_ANGLE_DOUBLE_RIGHT);
            $doSaveAndNext->setUseButtonTag(true);
            $actions->push($doSaveAndNext);
        }
    }

    public function getCMSUtils()
    {
        // Allow us to keep the extension point at no cost
        if (method_exists($this->owner, 'getBaseCMSUtils')) {
            $utils = $this->owner->getBaseCMSUtils();
        } else {
            $utils = new FieldList();
        }
        // Next/prev
        $controller = Controller::curr();
        $url = $controller->getRequest()->getURL();
        if ($this->owner->ID && $this->owner->hasMethod('NextRecord') && $NextRecord = $this->owner->NextRecord()) {
            $utils->unshift($NextBtnLink = new CmsInlineFormAction('NextBtnLink', 'Next >', 'btn-secondary'));
            $NextBtnLink->setUrl(str_replace('/' . $this->owner->ID . '/', '/' . $NextRecord->ID . '/', $url));
        }
        if ($this->owner->ID && $this->owner->hasMethod('PrevRecord') && $PrevRecord = $this->owner->PrevRecord()) {
            $utils->unshift($PrevBtnLink = new CmsInlineFormAction('PrevBtnLink', '< Previous', 'btn-secondary'));
            $PrevBtnLink->setUrl(str_replace('/' . $this->owner->ID . '/', '/' . $PrevRecord->ID . '/', $url));
        }
        $this->owner->extend('updateCMSUtils', $utils);
        return $utils;
    }
}
