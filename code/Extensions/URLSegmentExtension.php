<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\Parsers\URLSegmentFilter;

/**
 * Class \LeKoala\Base\Extensions\URLSegmentExtension
 *
 * @property \LeKoala\Base\News\NewsCategory|\LeKoala\Base\News\NewsItem|\LeKoala\Base\Tags\Tag|\LeKoala\Base\Extensions\URLSegmentExtension $owner
 * @property string $URLSegment
 */
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
        if (!$segment) {
            return false;
        }
        $class = get_class($this->owner);
        return $class::get()->exclude('ID', $this->owner->ID)->filter("URLSegment", $segment)->first();
    }
    public function getBaseURLSegment()
    {
        $segment = $this->owner->getTitle();
        if ($this->owner->hasMethod('updateURLSegment')) {
            $this->owner->updateURLSegment($segment);
        }
        $filter = new URLSegmentFilter();
        $baseSegment = $filter->filter($segment);
        if (\is_numeric($baseSegment)) {
            return false;
        }
        return $baseSegment;
    }
    public function generateURLSegment()
    {
        $baseSegment = $segment = $this->getBaseURLSegment();
        if (!$baseSegment) {
            return;
        }
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
        // Generate segment if no segment
        if (!$this->owner->URLSegment && $this->getBaseURLSegment()) {
            $this->owner->URLSegment = $this->generateURLSegment();
        }
    }
}
