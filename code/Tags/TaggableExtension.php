<?php
namespace LeKoala\Base\Tags;

use LeKoala\Base\Tags\Tag;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Forms\MultiSelect2Field;
use LeKoala\Base\Forms\Select2MultiField;

/**
 * Provides cross objects tag functionnality
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
        $Tags->setOnNewTag(function($tag) {
            $new = new Tag();
            $new->Title = $tag;
            $new->write();

            return $new->ID;
        });

        $fields->addFieldsToTab('Root.Main', $Tags);
    }

}
