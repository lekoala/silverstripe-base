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
 */
class VersionedGridFieldItemRequestExtension extends Extension
{
    public function pushCurrent()
    {
        Controller::curr()->pushCurrent();
    }
}
