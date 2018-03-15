<?php
namespace LeKoala\Base\Blocks;
use Page;
use LeKoala\Base\Blocks\Block;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Director;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\Contact\ContactSubmission;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use LeKoala\Base\ORM\FieldType\JSONText;
/**
 * A page mode of blocks
 *
 * Blocks html are rendered into the Content variable on save, so everthing
 * is statically compiled
 *
 * This means that blocks versioning will follow page versioning and everything
 * is published at the same time
 *
 * @property string $BlocksList
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Blocks\Block[] Blocks()
 */
class BlocksPage extends Page
{
    private static $table_name = 'BlocksPage'; // When using namespace, specify table name
    private static $db = [
        "BlocksList" => JSONText::class
    ];
    private static $has_many = [
        "Blocks" => Block::class
    ];
    public function updateBodyClass(&$class)
    {
        if (is_callable('parent::updateBodyClass')) {
            parent::updateBodyClass($class);
        }
        $arr = $this->getBlocksListArray();
        if (!empty($arr)) {
            $class .= ' Starts-' . $arr[0];
        }
    }
    /**
     * @return array
     */
    public function getBlocksListArray()
    {
        if (!$this->BlocksList) {
            return [];
        }
        return $this->dbObject('BlocksList')->decodeArray();
    }
    public function getContent()
    {
        if (isset($_GET['live']) && Director::isDev()) {
            return $this->renderContent(true);
        }
        return $this->getField('Content');
    }
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $BlocksConfig = GridFieldConfig_RecordEditor::create();
        $BlocksConfig->addComponent(new GridFieldOrderableRows());
        $BlocksConfig->removeComponentsByType(GridFieldPageCount::class);
        $BlocksConfig->removeComponentsByType(GridFieldPaginator::class);
        // We need to keep GridFieldToolbarHeader otherwise sorting does not work
        $Blocks = new GridField('Blocks', '', $this->Blocks(), $BlocksConfig);
        $fields->replaceField('Content', $Blocks);
        return $fields;
    }
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Content = $this->renderContent();
        $list = array_unique($this->Blocks()->column('Type'));
        $this->BlocksList = $list;
    }
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        // Cleanup blocks assets
        foreach ($this->Blocks() as $block) {
            if ($block->ImageID) {
                $block->Image()->delete();
            }
            // TODO: cleanup files in Data
        }
    }
    /**
     * Render all blocks to get a full html document
     *
     * @param boolean $refreshBlocks
     * @return string
     */
    public function renderContent($refreshBlocks = false)
    {
        $Content = '';
        foreach ($this->Blocks() as $Block) {
            $class = $Block->getClass();
            $Content .= '<section class="' . $class . '">';
            if ($refreshBlocks) {
                $Block->write();
            }
            $Content .= (string)$Block->forTemplate();
            $Content .= '</section>';
        }
        return $Content;
    }
}