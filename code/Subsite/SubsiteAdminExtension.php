<?php
namespace LeKoala\Base\Subsite;

use SilverStripe\Core\Extension;

class SubsiteAdminExtension extends Extension
{
    /**
     * Returns the target for the redirect of 'Save and Close'-Button
     * TODO: this does not fixe save and close from domains
     * @return string
     **/
    public function Backlink()
    {
        return 'admin/subsites';
    }
}
