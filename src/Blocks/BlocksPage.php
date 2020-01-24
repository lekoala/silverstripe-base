<?php
namespace LeKoala\Base\Blocks;

use Page;
use LeKoala\Base\Blocks\Block;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Dev\TaskRunner;

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
    /**
     * @config
     * @var boolean
     */
    private static $wrap_blocks = true;
    /**
     * Track writing to prevent infinite loop
     *
     * @var boolean
     */
    protected static $is_writing = false;

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
            $item = [
                'Link' => $this->Link() . '#' . $block->HTMLID,
                'Title' => $title,
                'MenuTitle' => $title,
            ];
            $list->push($item);
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
        // If you pass ?live, content of the block will always be fully rendered and written to the database
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
        if ($this->ID) {
            // We should refresh if a block has been updated later than the page
            $refreshBlocks = false;
            $maxBlockEdited = strtotime($this->Blocks()->max('LastEdited'));
            if ($maxBlockEdited > strtotime($this->LastEdited)) {
                $refreshBlocks = true;
            }
            // In site publisher, always refresh
            // $ctrl = Controller::curr();
            // if($ctrl instanceof TaskRunner) {
            //     $refreshBlocks = true;
            // }
            $this->Content = $this->renderContent($refreshBlocks);
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
        Block::$auto_update_page = false;
        $wrap = self::config()->wrap_blocks;
        foreach ($this->Blocks() as $Block) {
            if ($wrap) {
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
            }

            if ($refreshBlocks) {
                $Block->write();
            }
            $Content .= (string)$Block->forTemplate();
            if ($wrap) {
                $Content .= '</section>';
            }
        }
        Block::$auto_update_page = true;
        return $Content;
    }

    /**
     * Add a block to this page
     * Useful for programmatic scaffolding
     *
     * @param string $content
     * @param string $type
     * @return void
     */
    public function addBlock($content, $type = null)
    {
        $block = new Block();
        $block->Content = $content;
        if ($type) {
            $block->Type = $type;
        }
        $block->PageID = $this->ID;
        $block->write();
    }
}
