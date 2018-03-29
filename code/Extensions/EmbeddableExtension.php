<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;
use Embed\Embed;
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

    public function EmbeddedContent() {
        $embed = Embed::create($this->owner->EmbedURL);
        $html = $embed->code;
        return $html;
    }


}
