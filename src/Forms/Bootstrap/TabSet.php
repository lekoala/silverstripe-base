<?php
namespace LeKoala\Base\Forms\Bootstrap;

use SilverStripe\View\SSViewer;
use SilverStripe\Forms\TabSet as DefaultTabSet;

/**
 * @link https://getbootstrap.com/docs/4.0/components/navs/#javascript-behavior
 */
class TabSet extends DefaultTabSet
{
    public function getSelectedTab()
    {
        foreach ($this->Tabs() as $tab) {
            if ($tab->getSelected()) {
                return $tab;
            }
        }
        return $this->Tabs()->first();
    }

    public function hasSelectedTab()
    {
        foreach ($this->Tabs() as $tab) {
            if ($tab->getSelected()) {
                return true;
            }
        }
        return false;
    }

    public function FieldHolder($properties = array())
    {
        if (!$this->hasSelectedTab()) {
            $this->Tabs()->first()->setSelected(true);
        }
        $state = SSViewer::getRewriteHashLinksDefault();
        SSViewer::setRewriteHashLinksDefault(false);
        $result = (string) parent::FieldHolder($properties);
        SSViewer::setRewriteHashLinksDefault($state);
        return $result;
    }
}
