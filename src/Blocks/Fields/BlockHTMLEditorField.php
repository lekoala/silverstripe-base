<?php
namespace LeKoala\Base\Blocks\Fields;

use SilverStripe\View\Parsers\HTMLValue;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorSanitiser;

/**
 * This simple extension allow storing the html into json
 * and relies on our hijacked setCastedField method to save the html
 */
class BlockHTMLEditorField extends HTMLEditorField
{
    public function extraClass()
    {
        return 'htmleditor ' . parent::extraClass();
    }

    /**
     * @param DataObject|DataObjectInterface $record
     * @throws Exception
     */
    public function saveInto(DataObjectInterface $record)
    {
        // Sanitise if requested
        $htmlValue = HTMLValue::create($this->Value());
        if (HTMLEditorField::config()->sanitise_server_side) {
            $santiser = HTMLEditorSanitiser::create(HTMLEditorConfig::get_active());
            $santiser->sanitise($htmlValue);
        }

        // optionally manipulate the HTML after a TinyMCE edit and prior to a save
        $this->extend('processHTML', $htmlValue);

        // Store into record
        $record->setCastedField($this->name, $htmlValue->getContent());
    }
}
