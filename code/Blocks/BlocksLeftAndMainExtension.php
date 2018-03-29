<?php
namespace LeKoala\Base\Blocks;

use SilverStripe\Admin\LeftAndMainExtension;
/**
 * Class \LeKoala\Base\LeftAndMainExtension
 *
 * @property \SilverStripe\Admin\LeftAndMain|\SilverStripe\CMS\Controllers\CMSMain|\LeKoala\Base\Blocks\BlocksLeftAndMainExtension $owner
 */
class BlocksLeftAndMainExtension extends LeftAndMainExtension
{
    private static $allowed_actions = [
        'doPublishBlocks'
    ];
    public function doPublishBlocks()
    {
        $owner = $this->owner;
        $ID = $owner->getRequest()->param('ID');
        $Page = BlocksPage::get()->byID($ID);

        foreach ($Page->Blocks() as $Block) {
            $Block->write();
        }

        $Page->publishRecursive();

        // Publish in all other locales as well!
        if ($Page->has_extension("\\TractorCow\\Fluent\\Extension\\FluentExtension")) {
            $state = \TractorCow\Fluent\State\FluentState::singleton();
            $currentLocale = $state->getLocale();
            $allLocales = \TractorCow\Fluent\Model\Locale::get()->exclude('Locale', $currentLocale);
            foreach ($allLocales as $locale) {
                $state->withState(function ($state) use ($locale, $Page) {
                    $state->setLocale($locale->Locale);

                    foreach ($Page->Blocks() as $Block) {
                        $Block->write();
                    }

                    $Page->publishRecursive();
                });
            }
        }

        $message = "Blocks published";

        $owner->getResponse()->addHeader('X-Status', rawurlencode($message));
        return $owner->getResponseNegotiator()->respond($owner->getRequest());
    }
    public function init()
    {
    }
}
