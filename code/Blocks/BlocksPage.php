<?php
namespace LeKoala\Base\Blocks;

use LeKoala\Base\Blocks\Block;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Contact\ContactSubmission;
use SilverStripe\Forms\GridField\GridField;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

/**
 *
 */
class BlocksPage extends \Page
{
    private static $table_name = 'BlocksPage'; // When using namespace, specify table name

    private static $has_many = [
        "Blocks" => Block::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $BlocksConfig = GridFieldConfig_RecordEditor::create();
        $BlocksConfig->addComponent(new GridFieldOrderableRows());
        $Blocks = new GridField('Blocks',$this->fieldLabel('Blocks'), $this->Blocks(), $BlocksConfig);
        $fields->replaceField('Content', $Blocks);

        return $fields;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->Content = $this->renderContent();
    }

    public function renderContent() {
        $Content = '';
        foreach($this->Blocks() as $Block) {
            $class = $Block->getClass();
            $Content .= '<section class="'.$class.'">';
            $Content .= (string) $Block->forTemplate();
            $Content .= '</section>';
        }
        return $Content;
    }

}
