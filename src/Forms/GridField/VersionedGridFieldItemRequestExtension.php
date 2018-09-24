<?php
namespace LeKoala\Base\Forms\GridField;

use SilverStripe\Core\Extension;
use SilverStripe\Control\Controller;

/**
 * Because why would it work without this??
 *
 * Sigh...
 *
 * @link https://github.com/colymba/GridFieldBulkEditingTools/issues/174
 * @property \SilverStripe\Versioned\VersionedGridFieldItemRequest|\LeKoala\Base\Forms\GridField\VersionedGridFieldItemRequestExtension $owner
 */
class VersionedGridFieldItemRequestExtension extends Extension
{
    public function pushCurrent()
    {
        Controller::curr()->pushCurrent();
    }
}
