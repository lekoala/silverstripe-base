<?php

namespace LeKoala\Base\Subsite;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Middleware\AllowedHostsMiddleware;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Director;
use SilverStripe\Subsites\Model\SubsiteDomain;

/**
 * Class \LeKoala\Base\Subsite\SubsiteAdminExtension
 *
 * @property \SilverStripe\Subsites\Admin\SubsiteAdmin|\LeKoala\Base\Subsite\SubsiteAdminExtension $owner
 */
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

    public function init(): void
    {
        $allowedHostsMiddleware = Injector::inst()->get(AllowedHostsMiddleware::class, true);
        if ($allowedHostsMiddleware) {
            $force = isset($_GET['refresh_allowed_hosts']) && Permission::check('ADMIN');
            if (empty($allowedHostsMiddleware->getAllowedHosts()) || $force) {
                self::writeHostsListToEnv();
            }
        }
    }

    public static function writeHostsListToEnv(): void
    {
        $hosts = implode(',', self::generateHostsList());
        $env = Director::baseFolder() . '/.env';
        if (is_file($env) && is_writable($env)) {
            file_put_contents($env, "\nSS_ALLOWED_HOSTS=\"$hosts\"", FILE_APPEND);
        }
    }

    /**
     * @link https://docs.silverstripe.org/en/5/developer_guides/security/secure_coding/#request-hostname-forgery
     * @return array<string>
     */
    public static function generateHostsList(): array
    {
        $list = SubsiteDomain::get()->columnUnique('Domain');
        $host = Director::host();
        if (!in_array($host, $list)) {
            $list[] = $host;
        }
        return $list;
    }
}
