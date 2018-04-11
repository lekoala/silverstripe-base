<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
/**
 * Useful utilities for pages
 */
class BasePageExtension extends DataExtension
{

    public function requirePageForSegment($segment, $class, $data = [])
    {
        $page = SiteTree::get_by_link($segment);
        if ($page) {
            if ($page->ClassName != $class) {
                $page->ClassName = $class;
                $page->Write();
                $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
                $page->flushCache();
                DB::alteration_message($class . ' repaired', 'repaired');
            }
        } else {
            $page = new $class();
            foreach ($data as $k => $v) {
                $page->$k = $v;
            }
            $page->URLSegment = $segment;
            $page->write();
            $page->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
            $page->flushCache();
            DB::alteration_message($class . ' created', 'created');
        }
    }
}
