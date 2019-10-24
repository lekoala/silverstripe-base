<?php

namespace LeKoala\Base\Controllers;

use Exception;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Extensions\URLSegmentExtension;

/**
 * Apply this trait to your controllers managing records
 *
 * You can use onIndex and onView method to apply extra behaviour
 */
trait IsRecordController
{
    /**
     * We pass the ID first to have a consistent url schema
     * Index method will handle both case: with or without ID passed
     * In order to allow other url handlers, like a category handler
     * we need to add it ourself...
     */
    // private static $url_handlers = [
    //     '$ID/$Action' => 'handleAction',
    // ];

    /**
     * Get the record
     *
     * @return bool|DataObject
     */
    public function getRequestedRecord()
    {
        $class = get_called_class();
        if (!defined("$class::MODEL_CLASS")) {
            throw new Exception("You must define a MODEL_CLASS constant in your controller");
        }
        $ModelClass = self::MODEL_CLASS;
        $ModelClass_SNG = $ModelClass::singleton();
        $ID = $this->getRequest()->param('ID');
        if ($ID) {
            // Get by segment
            if ($ModelClass_SNG->hasExtension(URLSegmentExtension::class)) {
                return URLSegmentExtension::getByURLSegment($ModelClass, $ID);
            }
            // Fallback to id
            return DataObject::get_by_id($ModelClass, $ID);
        }
        return false;
    }

    public function getViewAction()
    {
        $class = get_called_class();
        if (defined("$class::MODEL_VIEW_ACTION")) {
            return self::MODEL_VIEW_ACTION;
        }
        return 'view';
    }

    public function index()
    {
        $url_handlers = $this->config()->url_handlers;
        if (!isset($url_handlers['$ID/$Action'])) {
            throw new Exception("Please add private static \$url_handlers to your class with \$ID/\$Action");
        }
        $ID = $this->getRequest()->param('ID');
        $page = $this->data();
        // We have a record : use a view action
        if ($ID) {
            $record = $this->getRequestedRecord();
            if (!$record) {
                return $this->httpError(404, "Record $ID not found");
            }
            if (!$record->canView()) {
                return $this->httpError(404, "Record $ID cannot be viewed");
            }

            $data = [
                'Item' => $record,
                "Title" => $record->getTitle(),
                "MetaTitle" => $record->getTitle() . ' ' . $page->getPageTitleSeparator() . ' ' . $page->getTitle()
            ];
            if (method_exists($this, 'onView')) {
                $extraData = $this->onView($record);
                if ($extraData && is_array($extraData)) {
                    $data = array_merge($data, $extraData);
                }
            }
            return $this->renderWith($this->getViewer($this->getViewAction()), $data);
        }
        // We don't have a record : use default action
        $data = [];
        if (method_exists($this, 'onIndex')) {
            $extraData = $this->onIndex();
            if ($extraData && is_array($extraData)) {
                $data = array_merge($data, $extraData);
            }
        }
        return $this->render($data);
    }
}
