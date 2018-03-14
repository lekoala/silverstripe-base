<?php
namespace LeKoala\Base\Tags;
use LeKoala\Base\Tags\Tag;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Forms\MultiSelect2Field;
use LeKoala\Base\Forms\Select2MultiField;
use SilverStripe\ORM\DB;
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
        $fields->addFieldsToTab('Root.Main', $Tags);
    }
    public function UsedTags($where = null)
    {
        $class = get_class($this->owner);
        $singl = $class::singleton();
        $table = $singl->baseTable();
        $sql = "SELECT TagID FROM {$table}_Tags";
        if ($where) {
            if (\is_array($where)) {
                $where = \implode(' AND ', $where);
            }
            $sql .= ' WHERE ' . $where;
        }
        $IDs = DB::query($sql)->column();
        return Tag::get()->filter('ID', $IDs);
    }
}