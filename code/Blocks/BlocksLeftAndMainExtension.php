<?php
namespace LeKoala\Base\Blocks;

use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\ORM\DB;

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

        Block::$auto_update_page = false;

        $DataCopy = [];
        $Blocks = $Page->Blocks();
        foreach ($Blocks as $Block) {
            $DataCopy[$Block->ID] = DB::query('SELECT Data FROM Block WHERE ID = ' . $Block->ID)->value();
            if (!$Block->Data) {
                $Block->Data = $DataCopy[$Block->ID];
            }
            $Block->write();
            // DB::prepared_query('UPDATE Block SET Data = ? WHERE ID = ' . $Block->ID, [$DataCopy[$Block->ID]]);
        }
        $Page->publishRecursive();

        // Publish in all other locales as well!
        if ($Page->has_extension("\\TractorCow\\Fluent\\Extension\\FluentExtension")) {
            $state = \TractorCow\Fluent\State\FluentState::singleton();
            $currentLocale = $state->getLocale();
            $allLocales = \TractorCow\Fluent\Model\Locale::get()->exclude('Locale', $currentLocale);
            foreach ($allLocales as $locale) {
                $state->withState(function ($state) use ($locale, $Page, $DataCopy) {
                    $state->setLocale($locale->Locale);

                    foreach ($Page->Blocks() as $Block) {
                        if (!$Block->Data) {
                            $Block->Data = $DataCopy[$Block->ID];
                        }
                        $Block->write();
                    }

                    $Page->publishRecursive();
                });
            }

            // Preserve original data
            // TODO: understand why Data is emptied
            foreach ($Blocks as $Block) {
                DB::prepared_query('UPDATE Block SET Data = ? WHERE ID = ' . $Block->ID, [$DataCopy[$Block->ID]]);
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
