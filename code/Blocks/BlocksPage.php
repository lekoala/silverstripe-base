<?php
namespace LeKoala\Base\Blocks;
use Page;
use LeKoala\Base\Blocks\Block;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Base\ORM\FieldType\JSONText;
use LeKoala\Base\Contact\ContactSubmission;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
/**
 * A page mode of blocks
 *
 * Blocks html are rendered into the Content variable on save, so everthing
 * is statically compiled
 *
 * This means that blocks versioning will follow page versioning and everything
 * is published at the same time
 *
 * @method \SilverStripe\ORM\DataList|\LeKoala\Base\Blocks\Block[] Blocks()
 */
class BlocksPage extends Page
{
    private static $table_name = 'BlocksPage'; // When using namespace, specify table name
    private static $has_many = [
        "Blocks" => Block::class
    ];
    private static $cascade_deletes = [
        "Blocks"
    ];
    protected static $is_writing = false;
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
     * This helper methods helps you to generate anchorable menu for your blocks
     *
     * @return ArrayList
     */
    public function MenuAnchorsItems()
    {
        $list = new ArrayList();
        $anchors = $this->Blocks()->exclude(['HTMLID' => null]);
        foreach ($anchors as $block) {
            $title = $block->MenuTitle;
            if (!$title) {
                $title = $block->Title;
            }
            $list->push([
                'Link' => $this->Link() . '#' . $block->HTMLID,
                'Title' => $title,
                'MenuTitle' => $title,
            ]);
        }
        return $list;
    }
    /**
     * @return array
     */
    public function getBlocksListArray()
    {
        return array_unique($this->Blocks()->column('Type'));
    }
    public function getContent()
    {
        if (isset($_GET['live']) && Director::isDev()) {
            return $this->renderContent(true);
        }
        return $this->getField('Content');
    }
    public function getCMSActions()
    {
        $fields = parent::getCMSActions();
        $fields->addFieldToTab('ActionMenus.MoreOptions', FormAction::create('doPublishBlocks', 'Publish all blocks'));
        return $fields;
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
        Block::$auto_update_page = false;
        foreach ($this->Blocks() as $Block) {
            $Content .= '<section';
            $htmlid = $Block->HTMLID;
            if ($htmlid) {
                $Content .= ' id="' . $htmlid . '"';
            }
            $class = $Block->getClass();
            if ($class) {
                $Content .= ' class="' . $class . '"';
            }
            $Content .= '>';
            if ($refreshBlocks) {
                $Block->write();
            }
            $Content .= (string)$Block->forTemplate();
            $Content .= '</section>';
        }
        Block::$auto_update_page = true;
        return $Content;
    }
}