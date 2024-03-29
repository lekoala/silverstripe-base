<?php

namespace LeKoala\Base\Admin;

use SilverStripe\Assets\File;
use SilverStripe\Core\Extension;

/**
 * Somehow the url field was removed ?
 *
 * @property \SilverStripe\AssetAdmin\Controller\AssetAdmin|\LeKoala\Base\Admin\AssetAdminLinksExtension $owner
 */
class AssetAdminLinksExtension extends Extension
{
    public function updateGeneratedThumbnails(File $file, &$links, $generator)
    {
        if (empty($links['url'])) {
            $links['url'] = $file->Link();
        }
    }
}
