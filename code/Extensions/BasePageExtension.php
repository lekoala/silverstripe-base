<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
use LeKoala\Base\Subsite\SubsiteHelper;

/**
 * Useful utilities for pages
 */
class BasePageExtension extends DataExtension
{

    /**
     * Easily require the page in requireDefaultRecords using this method
     *
     * @param string $segment Default url segment for the page
     * @param string $class The page class
     * @param array $data Data to inject in the page
     */
    public function requirePageForSegment($segment, $class, $data = [])
    {
        SubsiteHelper::withSubsites(function () use ($segment, $class, $data) {
            $page = SiteTree::get_by_link($segment);
            if ($page) {
                // We have a page but the class does not match
                if ($page->ClassName != $class) {
                    $page->ClassName = $class;
                    $page->writeAll();
                    $page->flushCache();
                    DB::alteration_message($class . ' repaired', 'repaired');
                } else {
                    // Do nothing, a page already exists
                }
            } else {
                $page = new $class();
                foreach ($data as $k => $v) {
                    $page->$k = $v;
                }
                $page->URLSegment = $segment;
                $page->writeAll();
                $page->flushCache();
                DB::alteration_message($class . ' created', 'created');
            }
        });
    }
}
