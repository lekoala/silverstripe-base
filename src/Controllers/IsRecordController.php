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
     */
    private static $url_handlers = [
        '$ID/$Action' => 'handleAction',
    ];

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
        $ID = $this->getRequest()->param('ID');
        // We have a record : use a view action
        if ($ID) {
            $record = $this->getRequestedRecord();
            $data = ['Item' => $record];
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
