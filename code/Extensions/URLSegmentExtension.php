<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\Parsers\URLSegmentFilter;

class URLSegmentExtension extends DataExtension
{
    private static $db = [
        "URLSegment" => "Varchar(191)",
    ];
    private static $indexes = [
        "URLSegmentUnique" => [
            "type" => "unique",
            "columns" => ["URLSegment"]
        ],
    ];

    public function updateCMSFields(FieldList $fields)
    {
    }

    public function validate(ValidationResult $validationResult)
    {
        $duplicate = $this->getDuplicateRecord();
        if ($duplicate) {
            $validationResult->addFieldError("URLSegment", "Segment already used by record #" . $duplicate->ID);
        }
    }

    public function getDuplicateRecord($segment = null)
    {
        if ($segment === null) {
            $segment = $this->owner->URLSegment;
        }
        $class = get_class($this->owner);
        return $class::get()->exclude('ID', $this->owner->ID)->filter("URLSegment", $segment)->first();
    }

    public function generateURLSegment()
    {
        $filter = new URLSegmentFilter();
        $baseSegment = $segment = $filter->filter($this->owner->getTitle());
        $duplicate = $this->getDuplicateRecord($segment);

        $i = 0;
        while ($duplicate) {
            $i++;
            $segment = $baseSegment . '-' . $i;
            $duplicate = $this->getDuplicateRecord($segment);
        }

        return $segment;
    }

    public function onBeforeWrite()
    {
        // Generate segment if no segment or numeric segment
        if (!$this->owner->URLSegment || is_numeric($this->owner->URLSegment)) {
            $this->owner->URLSegment = $this->generateURLSegment();
        }
    }

}
