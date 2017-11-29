<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;

class SlugExtension extends DataExtension
{
    private static $db = [
        "Slug" => "Varchar(191)",
    ];

    public function updateCMSFields(FieldList $fields)
    {
    }

    public function buildSlug()
    {
        $filter = new URLSegmentFilter();
        return $filter->filter($this->owner->getTitle());
    }

    public function onBeforeWrite()
    {
        if (!$this->owner->Slug) {
            $this->owner->Slug = $this->buildSlug();
        }
    }

}
