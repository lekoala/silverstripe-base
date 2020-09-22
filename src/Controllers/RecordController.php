<?php

namespace LeKoala\Base\Controllers;

use Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\HTTPRequest;
use LeKoala\Base\Extensions\URLSegmentExtension;

/**
 * This controller helps dealing with DataObject based records
 *
 * The ID is passed first so that we don't need to specify an ugly action in url
 *
 * The viewer template for the default action is _read
 *
 * Since it is difficult to change your base controller, it might be better
 * to apply IsRecordController to your own controller instead
 *
 */
class RecordController extends BaseContentController
{
    /**
     * We pass the ID first to have a consistent url schema
     * Index method will handle both case: with or without ID passed
     */
    private static $url_handlers = [
        '$ID/$Action' => 'handleAction',
    ];

    /**
     * The model class
     *
     * @var string
     */
    private static $model_class;

    /**
     * Get the record
     *
     * @return bool|DataObject
     */
    public function getRequestedRecord()
    {
        $ModelClass = self::config()->model_class;
        if (!$ModelClass) {
            throw new Exception("You must define a model_class static");
        }
        $request = $this->getRequest();
        $ModelClass_SNG = $ModelClass::singleton();
        $ID = $request->getHeader('X-RecordID');
        if (!$ID) {
            $ID = (int) $request->requestVar('_RecordID');
        }
        if ($ID) {
            if ($ModelClass_SNG->hasExtension(URLSegmentExtension::class)) {
                return URLSegmentExtension::getByURLSegment($ModelClass, $ID);
            }
            return DataObject::get_by_id($ModelClass, $ID);
        }
        return false;
    }

    public function index(HTTPRequest $request = null)
    {
        $ID = $this->getRequest()->param('ID');
        if ($ID) {
            return $this->renderWith($this->getViewer('read'), ['Item' => $this->getRequestedRecord()]);
        }
        return $this;
    }
}
