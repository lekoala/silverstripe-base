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

/**
 * The block dataobject is used to actually store the data
 */
final class Block extends DataObject
{
    private static $table_name = 'Block'; // When using namespace, specify table name

    private static $db = [
        'Type' => 'Varchar(191)',
        'Content' => 'HTMLText',
        'Data' => JSONText::class,
    ];
    private static $has_one = [
        "Image" => Image::class,
        "Page" => BlocksPage::class
    ];
    private static $many_many = [
        "Images" => Image::class,
        "Files" => File::class,
    ];
    private static $owns = [
        'Image'
    ];
    private static $summary_fields = [
        'TypeName', 'Summary'
    ];
    private static $defaults = [
        'Type' => ContentBlock::class,
    ];

    public function forTemplate()
    {
        return $this->Content;
    }

    public function getTitle()
    {
        $type = $this->TypeName();
        if ($this->ID) {
            return $type . ' #' . $this->ID;
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

            // TODO: we could iterate over the data list instead of relying on predefined key
            if (isset($data['Items'])) {
                $data['Items'] = self::normalizeIndexedList($data['Items']);
            }

            // Somehow, data is not nested properly if not wrapped beforehand with ArrayData
            $arrayData = new ArrayData($data);

            $result = (string)$typeInst->renderWith($template, $arrayData);
        }
        // Restore themes just in case to prevent any side effect
        SSViewer::set_themes($themes);

        return $result;
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
        $list = new ArrayList();

        foreach ($indexedList as $index => $item) {
            $item['ID'] = $index;
            $list->push($item);
        }

        return $list;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $Content = $this->renderWithTemplate();

        if ($Content) {
            $this->Content = $Content;
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        // Update Page Content
        $this->Page()->write();
    }

    protected static function getThemes()
    {
        return SSViewer::config()->themes;
    }

    protected static function getMainTheme()
    {
        $themes = self::getThemes();
        return $themes[0];
    }

    /**
     * Get a name for this type
     * Basically calling getBlockName with the Type
     *
     * @return string
     */
    public function TypeName()
    {
        if (!$this->Type) {
            'Undefined';
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
            return $this->getInData($name);
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
     * @return string
     */
    public function getInData($key)
    {
        $matches = self::extractNameParts($key);

        $arr = $this->DataArray();
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
            return \json_decode($this->Data, \JSON_OBJECT_AS_ARRAY);
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
        return 'Block Block-' . $this->TypeName();
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

        // Avoid escaping issues
        $text = new DBHTMLText('Summary');
        $text->setValue($shortSummary);

        return $text;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if (Director::isDev() && isset($_GET['debug'])) {
            $json = '';
            if ($this->Data) {
                $json = \json_encode(json_decode($this->Data), \JSON_PRETTY_PRINT);
            }
            $fields->addFieldsToTab('Root.Data', new LiteralField('JsonData', '<code>' . $json . '</code>'));
        }

        // Remove many many
        $fields->removeByName('Images');
        $fields->removeByName('Files');

        // Some default setup
        $ValidTypes = self::listValidTypes();
        $Type = new DropdownField('Type', $this->fieldLabel('Type'), $ValidTypes);
        $fields->replaceField('Type', $Type);

        $fields->removeByName('Content');

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
        $inst = $this->getTypeInstance();
        if (method_exists($inst, 'updateFields')) {
            $inst->updateFields($fields);
        }

        // Adjust uploaders
        $Image = $fields->dataFieldByName('Image');
        if ($Image) {
            $Image->setFolderName('Blocks/' . $this->PageID);
            $Image->setIsMultiUpload(false);
        }
        $dataFields = $fields->dataFields();
        foreach ($dataFields as $dataField) {
            if ($dataField instanceof UploadField) {
                $dataField->setFolderName('Blocks/' . $this->PageID . '/' . $this->TypeName());
                $dataField->setIsMultiUpload(false);
            }
        }

        // Always hide pages
        $Page = $fields->dataFieldByName('PageID');
        if ($Page) {
            $fields->replaceField('PageID', new HiddenField('PageID'));
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
