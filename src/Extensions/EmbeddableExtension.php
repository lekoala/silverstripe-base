<?php

namespace LeKoala\Base\Extensions;

use Embed\Embed;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;

/**
 * Class \LeKoala\Base\Extensions\EmbeddableExtension
 *
 * @property \LeKoala\Base\News\NewsItem|\LeKoala\Base\Extensions\EmbeddableExtension $owner
 * @property string $EmbedURL
 */
class EmbeddableExtension extends DataExtension
{
    private static $db = [
        "EmbedURL" => "Varchar(255)"
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $EmbedURL = $fields->dataFieldByName('EmbedURL');
        if ($EmbedURL) {
            $EmbedURL->setTitle(_t('EmbeddableExtension.EMBEDURL', 'Embed from URL'));

            $Content = $fields->dataFieldByName('Content');
            if ($Content) {
                $fields->insertAfter('Content', $EmbedURL);
            }
        }
    }

    public function validate(ValidationResult $validationResult)
    {
        if ($this->owner->EmbedURL) {
            try {
                $embed = Embed::create($this->owner->EmbedURL);
            } catch (Exception $ex) {
                $validationResult->addError($ex->getMessage());
            }
        }
    }

    public function EmbeddedContent()
    {
        $embed = Embed::create($this->owner->EmbedURL);
        $html = $embed->code;
        return $html;
    }
}
