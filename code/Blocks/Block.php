<?php
namespace LeKoala\Base\Blocks;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\ClassInfo;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Director;
use LeKoala\Base\Blocks\BaseBlock;
use LeKoala\Base\Blocks\BlocksPage;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use LeKoala\Base\Blocks\BlockButton;
use SilverStripe\Forms\DropdownField;
use LeKoala\Base\Helpers\ClassHelper;
use LeKoala\Base\ORM\FieldType\JSONText;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\Base\Blocks\Types\ContentBlock;
use LeKoala\Base\Extensions\SortableExtension;
use SilverStripe\AssetAdmin\Forms\UploadField;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use GuzzleHttp\Psr7\UploadedFile;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Parsers\URLSegmentFilter;

/**
 * The block dataobject is used to actually store the data
 *
 * @property string $Type
 * @property string $MenuTitle
 * @property string $HTMLID
 * @property string $Content
 * @property string $Data
 * @property string $Settings
 * @property int $Sort
 * @property int $ImageID
 * @property int $PageID
 * @method \SilverStripe\Assets\Image Image()
 * @method \LeKoala\Base\Blocks\BlocksPage Page()
 * @method \SilverStripe\ORM\ManyManyList|\SilverStripe\Assets\Image[] Images()
 * @method \SilverStripe\ORM\ManyManyList|\SilverStripe\Assets\File[] Files()
 * @mixin \LeKoala\Base\Extensions\SortableExtension
 * @mixin \LeKoala\Base\Extensions\SmartDataObjectExtension
 * @mixin \TractorCow\Fluent\Extension\FluentExtension
 */
final class Block extends DataObject
{
    const ITEMS_KEY = 'Items';
    private static $table_name = 'Block'; // When using namespace, specify table name
    private static $db = [
        'Type' => 'Varchar(59)',
        'MenuTitle' => 'Varchar(191)',
        'HTMLID' => 'Varchar(59)',
        'Content' => 'HTMLText',
        // Localized data
        'Data' => JSONText::class,
        // Unlocalized data
        'Settings' => JSONText::class,
    ];
    private static $has_one = [
        "Image" => Image::class,
        "Page" => BlocksPage::class
    ];
    private static $many_many = [
        "Images" => Image::class,
        "Files" => File::class,
    ];
    private static $many_many_extraFields = [
        'Images' => ['SortOrder' => 'Int'],
        'Files' => ['SortOrder' => 'Int'],
    ];
    private static $cascade_deletes = [
        'Image', 'Images', 'Files'
    ];
    private static $owns = [
        'Image'
    ];
    private static $summary_fields = [
        'BlockType' => 'Block Type',
        'Summary' => 'Summary',
    ];
    private static $translate = [
        "MenuTitle", "Content", "Data"
    ];
    private static $defaults = [
        'Type' => ContentBlock::class,
    ];
    public static $auto_update_page = true;
    public function forTemplate()
    {
        return $this->Content;
    }
    public function getTitle()
    {
        $type = $this->BlockType();
        if ($this->ID) {
            return $type;
        }
        return 'New ' . $type;
    }
    /**
     * Each block type can have one "collection" of items
     *
     * The collection is filtered according to the blocs id
     * (a relation must be set on the related class)
     *
     * @return DataList
     */
    public function Collection()
    {
        $inst = $this->getTypeInstance();
        $list = $inst->Collection();
        if ($list) {
            return $list->filter('BlockID', $this->ID);
        }
    }
    /**
     * Each block type can have one shared "collection" of items
     *
     * The shared collection is common across all blocks of the same type
     *
     * @return DataList
     */
    public function SharedCollection()
    {
        $inst = $this->getTypeInstance();
        return $inst->SharedCollection();
    }
    /**
     * Get sorted images
     *
     * @return \SilverStripe\ORM\ManyManyList
     */
    public function SortedImages()
    {
        return $this->Images()->Sort('SortOrder');
    }
    /**
     * Get sorted files
     *
     * @return \SilverStripe\ORM\ManyManyList
     */
    public function SortedFiles()
    {
        return $this->Files()->Sort('SortOrder');
    }
    public function renderWithTemplate()
    {
        $template = 'Blocks/' . $this->BlockClass();
        // Make sure theme exists in the list (not set in cms)
        $themes = SSViewer::get_themes();
        $configThemes = SSViewer::config()->themes;
        SSViewer::set_themes($configThemes);
        $chosenTemplate = SSViewer::chooseTemplate($template);
        // Render template
        $result = null;
        if ($chosenTemplate) {
            $typeInst = $this->getTypeInstance();
            $data = $this->DataArray();
            $settings = $this->SettingsArray();
            $data = array_merge($data, $settings);
            // We have items to normalize
            if (isset($data[self::ITEMS_KEY])) {
                $data[self::ITEMS_KEY] = self::normalizeIndexedList($data[self::ITEMS_KEY]);
            }
            // Somehow, data is not nested properly if not wrapped beforehand with ArrayData
            $arrayData = new ArrayData($data);
            // Maybe we need to disable hash rewriting
            if ($typeInst->hasMethod('disableAnchorRewriting')) {
                SSViewer::setRewriteHashLinksDefault($typeInst->disableAnchorRewriting());
            }
            $result = (string)$typeInst->renderWith($template, $arrayData);
            SSViewer::setRewriteHashLinksDefault(true);
        }
        // Restore themes just in case to prevent any side effect
        SSViewer::set_themes($themes);
        return $result;
    }
    /**
     * Class helper to use in your templates
     *
     * @param string $name
     * @return string
     */
    public function Cls($name)
    {
        return 'Block-' . $this->BlockType() . '-' . $name;
    }
    /**
     * Convert an indexed array to an ArrayList
     * This allows loops, etc in the template
     *
     * @param array $indexedList
     * @return ArrayList
     */
    protected static function normalizeIndexedList($indexedList)
    {
        static $counter = 0;
        $list = new ArrayList();
        $i = 0;
        $c = count($indexedList);
        foreach ($indexedList as $index => $item) {
            $i++;
            $counter++;
            // Add standard iterator stuff
            $FirstLast = '';
            if ($i === 1) {
                $FirstLast = 'first';
            } elseif ($i === $c) {
                $FirstLast = 'last';
            }
            $item['Pos'] = $index;
            $item['Counter'] = $counter;
            $item['FirstLast'] = $FirstLast;
            $item['EvenOdd'] = $i % 2 ? 'even' : 'odd';
            // Handle files
            foreach ($item as $k => $v) {
                if (is_array($v) && !empty($v['Files'])) {
                    $files = $v['Files'];
                    if (count($files) == 1) {
                        $item[$k] = self::getPublishedImageByID($files[0]);
                    } else {
                        $imageList = new ArrayList();
                        foreach ($files as $fileID) {
                            $imageList->push(self::getPublishedImageByID($fileID));
                        }
                        $item[$k] = $imageList;
                    }
                }
            }
            $list->push($item);
        }
        return $list;
    }
    /**
     * @param int $ID
     * @return Image
     */
    public static function getPublishedImageByID($ID)
    {
        $image = Image::get()->byID($ID);
        // This is just annoying
        if (!$image->isPublished()) {
            $image->doPublish();
        }
        return $image;
    }
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->HTMLID && $this->MenuTitle) {
            $filter = new URLSegmentFilter;
            $this->HTMLID = $filter->filter($this->MenuTitle);
        }
        $Content = $this->renderWithTemplate();
        if ($Content) {
            $this->Content = $Content;
        }
    }
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (self::$auto_update_page) {
            // Update Page Content to reflect updated block content
            $this->Page()->write();
        }
    }
    /**
     * Get a name for this type
     * Basically calling getBlockName with the Type
     *
     * @return string
     */
    public function BlockType()
    {
        if (!$this->Type) {
            '(Undefined)';
        }
        return self::getBlockName($this->Type);
    }
    /**
     * Get unqualified class of the block's type
     *
     * @return void
     */
    public function BlockClass()
    {
        return ClassHelper::getClassWithoutNamespace($this->Type);
    }
    /**
     * Extend __get to allow loading data from Data store
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        // A Data field
        if (strpos($name, 'Data[') === 0) {
            return $this->getIn($name, $this->DataArray());
        }
        // A Settings field
        if (strpos($name, 'Settings[') === 0) {
            return $this->getIn($name, $this->SettingsArray());
        }
        return parent::__get($name);
    }
    /**
     * Extend hasField to allow loading data from Data store
     *
     * @param string $name
     * @return mixed
     */
    public function hasField($name)
    {
        // A Data field
        if (strpos($name, 'Data[') === 0) {
            return true;
        }
        // A Settings field
        if (strpos($name, 'Settings[') === 0) {
            return true;
        }
        return parent::hasField($name);
    }
    /**
     * Split Name[Input][Sub][Value] notation
     *
     * @param string $name
     * @return array
     */
    public static function extractNameParts($name)
    {
        if (strpos($name, '[') !== false) {
            $matches = null;
            \preg_match_all('/\[([a-zA-Z0-9_]+)\]/', $name, $matches);
            $matches = $matches[1];
        } else {
            $matches = [$name];
        }
        return $matches;
    }
    /**
     * Get nested data
     *
     * @param string $key
     * @param array $arr
     * @return string
     */
    public function getIn($key, $arr)
    {
        $matches = self::extractNameParts($key);
        $val = $arr;
        foreach ($matches as $part) {
            if (isset($val[$part])) {
                $val = $val[$part];
            } else {
                $val = null;
            }
        }
        return $val;
    }
    /**
     * Consistently returns an array regardless of what is in Data
     *
     * @return array
     */
    public function DataArray()
    {
        if ($this->Data) {
            return json_decode($this->Data, JSON_OBJECT_AS_ARRAY);
        }
        return [];
    }
    /**
     * Consistently returns an array regardless of what is in Settings
     *
     * @return array
     */
    public function SettingsArray()
    {
        if ($this->Settings) {
            return json_decode($this->Settings, JSON_OBJECT_AS_ARRAY);
        }
        return [];
    }
    /**
     * When looping in template, wrap the blocks content is wrapped in a
     * div with theses classes
     *
     * @return string
     */
    public function getClass()
    {
        return 'Block Block-' . $this->BlockType();
    }
    /**
     * Get a viewable block instance wrapping this block
     *
     * @return BaseBlock
     */
    public function getTypeInstance()
    {
        if ($this->Type) {
            $class = $this->Type;
            if (class_exists($class)) {
                return new $class($this);
            }
        }
        return new ContentBlock($this);
    }
    /**
     * Returns a summary to be displayed in the gridfield
     *
     * @return DBHTMLText
     */
    public function Summary()
    {
        // Read from content
        $summary = trim(\strip_tags($this->Content));
        $shortSummary = \substr($summary, 0, 100);
        // Collapse whitespace
        $shortSummary = preg_replace('/\s+/', ' ', $shortSummary);
        if (!$shortSummary) {
            if ($this->ImageID) {
                $shortSummary = $this->Image()->getTitle();
            }
        }
        // Avoid escaping issues
        $text = new DBHTMLText('Summary');
        $text->setValue($shortSummary);
        return $text;
    }
    public function getCMSActions()
    {
        $actions = parent::getCMSActions();
        return $actions;
    }
    public function getCMSFields()
    {
        // $fields = parent::getCMSFields();
        $fields = new BlockFieldList();
        $mainTab = new Tab("Main");
        $settingsTab = new Tab("Settings");
        $fields->push(new TabSet("Root", $mainTab, $settingsTab));
        // ! Fields must be added to Root.Main to work properly
        $mainTab->push(new HiddenField('ID'));
        $mainTab->push(new HiddenField('Data'));
        $mainTab->push(new HiddenField('Settings'));
        $mainTab->push(new HiddenField('PageID'));
        // Show debug infos
        if (Director::isDev() && isset($_GET['debug'])) {
            $json = '';
            if ($this->Data) {
                $json = \json_encode(json_decode($this->Data), \JSON_PRETTY_PRINT);
                $debugData = new LiteralField('JsonData', '<pre>Data: <code>' . $json . '</code></pre>');
            } else {
                $debugData = new LiteralField('JsonData', '<div class="message info">Does not contain any data</div>');
            }
            $fields->addFieldsToTab('Root.Debug', $debugData);
            if ($this->Settings) {
                $json = \json_encode(json_decode($this->Settings), \JSON_PRETTY_PRINT);
                $debugSettings = new LiteralField('JsonSettings', '<pre>Settings: <code>' . $json . '</code></pre>');
            } else {
                $debugSettings = new LiteralField('JsonSettings', '<div class="message info">Does not contain any settings</div>');
            }
            $fields->addFieldsToTab('Root.Debug', $debugSettings);
        }
        // Only show valid types in a dropdown
        $ValidTypes = self::listValidTypes();
        $Type = new DropdownField('Type', $this->fieldLabel('Type'), $ValidTypes);
        $Type->setAttribute('onchange', "jQuery('#Form_ItemEditForm_action_doSave').click()");
        if ($this->ID) {
            $settingsTab->push($Type);
        } else {
            $mainTab->push($Type);
        }
        // Other settings
        $settingsTab->push(new TextField('MenuTitle', 'Menu Title'));
        $settingsTab->push(new TextField('HTMLID', 'HTML ID'));
        // Show uploader
        $Image = UploadField::create('Image');
        $fields->addFieldsToTab('Root.Main', $Image);
        // Handle Collection GridField
        $list = $this->Collection();
        if ($list) {
            $class = $list->dataClass();
            $singleton = $class::singleton();
            $gridConfig = new GridFieldConfig_RecordEditor();
            if ($singleton->hasExtension(SortableExtension::class)) {
                $gridConfig->addComponent(new GridFieldOrderableRows());
            }
            $grid = new GridField($class, $singleton->plural_name(), $list, $gridConfig);
            $fields->addFieldToTab('Root.Main', $grid);
        }
        // Handle Shared Collection GridField
        $list = $this->SharedCollection();
        if ($list) {
            $class = $list->dataClass();
            $singleton = $class::singleton();
            $gridConfig = new GridFieldConfig_RecordEditor();
            if ($singleton->hasExtension(SortableExtension::class)) {
                $gridConfig->addComponent(new GridFieldOrderableRows());
            }
            $grid = new GridField($class, $singleton->plural_name() . ' (shared)', $list, $gridConfig);
            $fields->addFieldToTab('Root.Main', $grid);
        }
        // Allow type instance to extends fields
        // Defined fields are processed later on for default behaviour
        $inst = $this->getTypeInstance();
        $inst->updateFields($fields);
        // Allow regular extension to work
        $this->extend('updateCMSFields', $fields);
        // Adjust uploaders
        $uploadFolder = 'Blocks/' . $this->PageID;
        $Image = $fields->dataFieldByName('Image');
        if ($Image) {
            $Image->setFolderName($uploadFolder);
            $Image->setAllowedMaxFileNumber(1);
            $Image->setIsMultiUpload(false);
        }
        $dataFields = $fields->dataFields();
        foreach ($dataFields as $dataField) {
            if ($dataField instanceof UploadField) {
                $dataField->setFolderName($uploadFolder . '/' . $this->BlockType());
                $dataField->setAllowedMaxFileNumber(1);
                $dataField->setIsMultiUpload(false);
            }
        }
        return $fields;
    }
    public function validate()
    {
        $result = parent::validate();
        return $result;
    }
    /**
     * List all classes extending BaseBlock
     *
     * @return array
     */
    public static function listBlocks()
    {
        $blocks = ClassInfo::subclassesFor(BaseBlock::class);
        // Remove BaseBlock
        \array_shift($blocks);
        return $blocks;
    }
    /**
     * Get a list of blocks mapped by class => name
     *
     * @return void
     */
    public static function listValidTypes()
    {
        $list = [];
        foreach (self::listBlocks() as $lcClass => $class) {
            $list[$class] = self::getBlockName($class);
        }
        return $list;
    }
    /**
     * Get a list of blocks mapped by unqualified class => class
     *
     * @return void
     */
    public static function listTemplates()
    {
        $list = [];
        foreach (self::listBlocks() as $lcClass => $class) {
            $list[ClassHelper::getClassWithoutNamespace($class)] = $class;
        }
        return $list;
    }
    /**
     * Get a more human readable name
     * TODO: i18n
     *
     * @param string $class
     * @return string
     */
    protected static function getBlockName($class)
    {
        $class = ClassHelper::getClassWithoutNamespace($class);
        return str_replace('Block', '', $class);
    }
}
