<?php

namespace LeKoala\Base\Tags;

use SilverStripe\ORM\DB;
use LeKoala\Base\Tags\Tag;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Forms\Select2MultiField;

/**
 * Provides cross objects tag functionnality
 *
 * @property \LeKoala\Base\News\NewsItem|\LeKoala\Base\Tags\TaggableExtension $owner
 * @method \SilverStripe\ORM\ManyManyList|\LeKoala\Base\Tags\Tag[] Tags()
 */
class TaggableExtension extends DataExtension
{
    private static $many_many = [
        "Tags" => Tag::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $list = Tag::get()->map()->toArray();
        $Tags = new Select2MultiField("Tags", "Tags", $list);
        $Tags->setTags(true);
        $Tags->setOnNewTag(function ($tag) {
            $new = new Tag();
            $new->Title = $tag;
            $new->write();
            return $new->ID;
        });
        // Make sure we don't get an extra tab
        $fields->removeByName('Tags');
        $fields->addFieldsToTab('Root.Main', $Tags);
    }

    /**
     * Get all tags for objects of this class
     *
     * @param string|array $where
     * @return DataList|Tag[]
     */
    public function UsedTags($where = null)
    {
        $class = get_class($this->owner);
        $singl = $class::singleton();
        $table = $singl->baseTable();
        $sql = "SELECT TagID FROM {$table}_Tags";
        if ($where) {
            if (is_array($where)) {
                $where = implode(' AND ', $where);
            }
            $sql .= ' WHERE ' . $where;
        }
        $IDs = DB::query($sql)->column();
        return Tag::get()->filter('ID', $IDs);
    }


    /**
     * Get all items with the same tags
     *
     * @param integer $count
     * @return DataList
     */
    public function RelatedItems($count = 4)
    {
        $class = get_class($this->owner);
        $singl = $class::singleton();
        $table = $singl->baseTable();
        $tagIds = $this->owner->Tags()->column('ID');
        if (empty($tagIds)) {
            return false;
        }
        $list = implode(',', $tagIds);
        // TODO: check a proper way to do this
        $idColumn = $class . 'ID';
        $sql = "SELECT $idColumn FROM {$table}_Tags WHERE TagID IN ($list) AND $idColumn != " . $this->owner->ID;
        $IDs = DB::query($sql)->column();
        if (empty($IDs)) {
            return false;
        }
        return $class::get()->filter('ID', $IDs)->limit($count);
    }
}
