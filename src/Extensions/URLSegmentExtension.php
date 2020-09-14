<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\Parsers\URLSegmentFilter;

/**
 * URL Segment extension
 *
 * By default link will be applied WITHOUT actions
 * see IsRecordController trait to see how it's done
 *
 * @property \PortfolioCategory|\PortfolioItem|\LeKoala\Base\News\NewsCategory|\LeKoala\Base\News\NewsItem|\LeKoala\Base\Tags\Tag|\LeKoala\Base\Extensions\URLSegmentExtension $owner
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

    /**
     * Get a class by URLSegment
     *
     * @param string $class
     * @param string $URLSegment
     * @return DataObject
     */
    public static function getByURLSegment($class, $URLSegment)
    {
        return $class::get()->filter('URLSegment', $URLSegment)->first();
    }

    public function updateCMSFields(FieldList $fields)
    {
        $URLSegment = $fields->dataFieldByName('URLSegment');
        if ($URLSegment) {
            $URLSegment->setTitle(_t('URLSegmentExtension.URLSEGMENT', 'URL Segment'));

            $Title = $fields->dataFieldByName('Title');
            if ($Title) {
                $fields->insertAfter('Title', $URLSegment);
            }
        }
    }

    /**
     * @return boolean
     */
    public function isSearchable()
    {
        return $this->owner->hasMethod('Page');
    }

    /**
     * We have this link method by default that allows us to define
     * a link for this record
     *
     * You can define your own methods in your DataObject class
     *
     * @param string $action
     * @return string
     */
    public function Link($action = null)
    {
        $url = $this->owner->Page()->Link($this->owner->URLSegment);
        if ($action) {
            $url .= "/$action";
        }
        return $url;
    }

    /**
     * Cannot use the same url segment
     *
     * @param ValidationResult $validationResult
     * @return void
     */
    public function validate(ValidationResult $validationResult)
    {
        $duplicate = $this->getDuplicateRecord();
        if ($duplicate) {
            $validationResult->addFieldError("URLSegment", "Segment already used by record #" . $duplicate->ID);
        }
    }

    /**
     * Find another record with the same url segment
     *
     * @param string $segment
     * @return DataObject
     */
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

    /**
     * This method allows you to customize url segment generation
     *
     * By default, URL segment is based on page title
     *
     * @return string
     */
    public function getBaseURLSegment()
    {
        $segment = $this->owner->getTitle();
        if ($this->owner->hasMethod('updateURLSegment')) {
            $this->owner->updateURLSegment($segment);
        }
        $filter = new URLSegmentFilter();
        $baseSegment = $filter->filter($segment);
        if (is_numeric($baseSegment)) {
            return false;
        }
        return $baseSegment;
    }

    /**
     * Generate a new url segment and checks for duplicates
     *
     * @return string
     */
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
